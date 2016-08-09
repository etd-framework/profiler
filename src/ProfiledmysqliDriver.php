<?php
/**
 * Part of the ETD Framework Profiler Package
 *
 * @copyright   Copyright (C) 2016 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace Joomla\Database\Profiledmysqli;

use Joomla\Database\Mysqli\MysqliDriver;
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
        $sql = $this->replacePrefix((string)$this->sql);

        if ($this->limit > 0 || $this->offset > 0) {
            $sql .= ' LIMIT ' . $this->offset . ', ' . $this->limit;
        }

        // Increment the query counter.
        $this->count++;

        // If debugging is enabled then let's log the query.
        if ($this->debug) {

            // Add the query to the object queue.
            $this->log[] = $sql;

            $this->timings[] = microtime(true);

            if (is_object($this->cursor)) {
                // Avoid warning if result already freed by third-party library
                @$this->freeResult();
            }

            $memoryBefore = memory_get_usage();
        }

        // Reset the error values.
        $this->errorNum = 0;
        $this->errorMsg = '';

        // Execute the query. Error suppression is used here to prevent warnings/notices that the connection has been lost.
        $this->cursor = @mysqli_query($this->connection, $sql);

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
        if (!$this->cursor) {
            $this->errorNum = (int)mysqli_errno($this->connection);
            $this->errorMsg = (string)mysqli_error($this->connection) . "\n-- SQL --\n" . $sql;

            // Check if the server was disconnected.
            if (!$this->connected()) {
                try {
                    // Attempt to reconnect.
                    $this->connection = null;
                    $this->connect();
                } catch (\RuntimeException $e) // If connect fails, ignore that exception and throw the normal exception.
                {
                    $this->log(Log\LogLevel::ERROR, 'Database query failed (error #{code}): {message}', [
                        'code'    => $this->errorNum,
                        'message' => $this->errorMsg
                    ]);

                    throw new \RuntimeException($this->errorMsg, $this->errorNum);
                }

                // Since we were able to reconnect, run the query again.
                return $this->execute();
            }

            // The server was not disconnected.
            $this->log(Log\LogLevel::ERROR, 'Database query failed (error #{code}): {message}', [
                'code'    => $this->errorNum,
                'message' => $this->errorMsg
            ]);

            throw new \RuntimeException($this->errorMsg, $this->errorNum);
        }

        return $this->cursor;
    }

    /**
     * Disconnects the database.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function disconnect() {

        if ($this->connection instanceof mysqli && $this->connection->stat() !== false) {
            if ($this->disconnectHandler) {
                call_user_func_array($this->disconnectHandler, array(&$this));
            }
        }

        parent::disconnect();
    }

}