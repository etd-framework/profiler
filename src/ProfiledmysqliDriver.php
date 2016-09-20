<?php
/**
 * Part of the ETD Framework Profiler Package
 *
 * @copyright   Copyright (C) 2016 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace Joomla\Database\Profiledmysqli;

use Joomla\Database\Exception\ConnectionFailureException;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\Mysqli\MysqliDriver;
use Joomla\Database\Query\PreparableInterface;
use mysqli;
use Psr\Log;

/**
 * MySQLi Database Driver
 *
 * @see    http://php.net/manual/en/book.mysqli.php
 * @since  1.0
 */
class ProfiledmysqliDriver extends MysqliDriver {

	/**
	 * @var    array  The log of executed SQL statements by the database driver.
	 */
	protected $log = [];

	/**
	 * @var    array  The log of executed SQL statements timings (start and stop microtimes) by the database driver.
	 */
	protected $timings = [];

	/**
	 * @var    array  The log of executed SQL statements timings (start and stop microtimes) by the database driver.
	 */
	protected $callStacks = [];

	/**
	 * @var callable
	 */
	protected $disconnectHandler;

	/**
	 * @return array
	 */
	public function getLog() {

		return $this->log;
	}

	/**
	 * @return array
	 */
	public function getCallStacks() {

		return $this->callStacks;
	}

	/**
	 * @return array
	 */
	public function getTimings() {

		return $this->timings;
	}

	/**
	 * @param callable $disconnectHandler
	 */
	public function setDisconnectHandler($disconnectHandler) {

		$this->disconnectHandler = $disconnectHandler;
	}

	public function __construct(array $options) {

		parent::__construct($options);

		$this->log        = [];
		$this->callStacks = [];
		$this->timings    = [];

	}

	/**
	 * Destructor.
	 *
	 * @since   1.0
	 */
	public function __destruct() {

		$this->disconnect();
	}

	/**
	 * Execute the SQL statement.
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute() {

		$this->connect();

		// Take a local copy so that we don't modify the original query and cause issues later
		$sql = $this->replacePrefix((string) $this->sql);

		// If debugging is enabled then let's log the query.
		if ($this->debug) {

			// Increment the query counter.
			$this->count++;

			// Add the query to the object queue.
			$this->log[] = $sql;

			$this->timings[] = microtime(true);

			$memoryBefore = memory_get_usage();
		}

		// Reset the error values.
		$this->errorNum = 0;
		$this->errorMsg = '';

		// Execute the query.
		$this->executed = false;

		if ($this->prepared instanceof \mysqli_stmt) {

			// Bind the variables:
			if ($this->sql instanceof PreparableInterface) {

				$bounded =& $this->sql->getBounded();

				if (count($bounded)) {

					$params     = [];
					$typeString = '';

					foreach ($bounded as $key => $obj) {

						// Add the type to the type string
						$typeString .= $obj->dataType;

						// And add the value as an additional param
						$params[] = $obj->value;
					}

					// Make everything references for call_user_func_array()
					$bindParams   = array();
					$bindParams[] = &$typeString;

					for ($i = 0; $i < count($params); $i++) {
						$bindParams[] = &$params[$i];
					}

					call_user_func_array([
						$this->prepared,
						'bind_param'
					], $bindParams);
				}
			}

			$this->executed = $this->prepared->execute();
			$this->cursor   = $this->prepared->get_result();

			if ($this->debug) {

				$this->timings[]    = microtime(true);
				$this->callStacks[] = debug_backtrace();

				$this->callStacks[count($this->callStacks) - 1][0]['memory'] = [
					$memoryBefore,
					memory_get_usage(),
					is_object($this->cursor) ? $this->getNumRows() : null
				];
			}

			// If the query was successful and we did not get a cursor, then set this to true (mimics mysql_query() return)
			if ($this->executed && !$this->cursor) {
				$this->cursor = true;
			}
		}

		// If an error occurred handle it.
		if (!$this->executed) {

			$this->errorNum = (int) $this->connection->errno;
			$this->errorMsg = (string) $this->connection->error;

			// Check if the server was disconnected.
			if (!$this->connected()) {

				try {
					// Attempt to reconnect.
					$this->connection = null;
					$this->connect();
				} catch (ConnectionFailureException $e) { // If connect fails, ignore that exception and throw the normal exception.
					$this->log(Log\LogLevel::ERROR, 'Database query failed (error #{code}): {message}; Failed query: {sql}', [
							'code'    => $this->errorNum,
							'message' => $this->errorMsg,
							'sql'     => $sql,
							'trace'   => debug_backtrace()
						]);

					throw new ExecutionFailureException($sql, $this->errorMsg, $this->errorNum);
				}

				// Since we were able to reconnect, run the query again.
				return $this->execute();
			}

			// The server was not disconnected.
			$this->log(Log\LogLevel::ERROR, 'Database query failed (error #{code}): {message}; Failed query: {sql}', [
					'code'    => $this->errorNum,
					'message' => $this->errorMsg,
					'sql'     => $sql,
					'trace'   => debug_backtrace()
				]);

			throw new ExecutionFailureException($sql, $this->errorMsg, $this->errorNum);
		}

		return $this->cursor;
	}

	/**
	 * Internal method to execute queries which cannot be run as prepared statements.
	 *
	 * @param   string  $sql  SQL statement to execute.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function executeUnpreparedQuery($sql) {

		$this->connect();

		// If debugging is enabled then let's log the query.
		if ($this->debug) {

			// Increment the query counter.
			$this->count++;

			// Add the query to the object queue.
			$this->log[] = $sql;

			$this->timings[] = microtime(true);

			if (is_object($this->cursor)) {
				// Avoid warning if result already freed by third-party library
				@$this->freeResult();
			}

			$memoryBefore = memory_get_usage();
		}

		$cursor = $this->connection->query($sql);

		if ($this->debug) {

			$this->timings[]    = microtime(true);
			$this->callStacks[] = debug_backtrace();

			$this->callStacks[count($this->callStacks) - 1][0]['memory'] = [
				$memoryBefore,
				memory_get_usage(),
				is_object($this->cursor) ? $this->getNumRows() : null
			];
		}

		// If an error occurred handle it.
		if (!$cursor) {
			$this->errorNum = (int) $this->connection->errno;
			$this->errorMsg = (string) $this->connection->error;

			// Check if the server was disconnected.
			if (!$this->connected()) {
				try {
					// Attempt to reconnect.
					$this->connection = null;
					$this->connect();
				} catch (ConnectionFailureException $e) { // If connect fails, ignore that exception and throw the normal exception.
					$this->log(
						Log\LogLevel::ERROR,
						'Database query failed (error #{code}): {message}; Failed query: {sql}',
						['code' => $this->errorNum, 'message' => $this->errorMsg, 'sql' => $sql, 'trace' => debug_backtrace()]
					);

					throw new ExecutionFailureException($sql, $this->errorMsg, $this->errorNum);
				}

				// Since we were able to reconnect, run the query again.
				return $this->executeUnpreparedQuery($sql);
			}

			// The server was not disconnected.
			$this->log(
				Log\LogLevel::ERROR,
				'Database query failed (error #{code}): {message}; Failed query: {sql}',
				['code' => $this->errorNum, 'message' => $this->errorMsg, 'sql' => $sql, 'trace' => debug_backtrace()]
			);

			throw new ExecutionFailureException($sql, $this->errorMsg, $this->errorNum);
		}

		$this->freeResult($cursor);

		return true;
	}

	/**
	 * Disconnects the database.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function disconnect() {

		if ($this->connection instanceof mysqli && $this->connection->stat() !== false)
		{
			if ($this->disconnectHandler)
			{
				call_user_func_array($this->disconnectHandler, array(&$this));
			}
		}

		parent::disconnect();
	}

}