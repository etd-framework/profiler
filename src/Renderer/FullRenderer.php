<?php
/**
 * Part of the ETD Framework Profiler Package
 *
 * @copyright   Copyright (C) 2016 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Profiler\Renderer;

use Joomla\Profiler\ProfilerRendererInterface;
use Joomla\Profiler\ProfilerInterface;
use Joomla\Utilities\ArrayHelper;

/**
 * Default profiler renderer.
 *
 * @since  1.0
 */
class FullRenderer implements ProfilerRendererInterface {

    /**
     * xdebug.file_link_format from the php.ini.
     *
     * @var    string
     * @since  1.7
     */
    protected $linkFormat = '';

    /**
     * Holds total amount of executed queries.
     *
     * @var    int
     */
    private $totalQueries = 0;

    private $container;

    private static $loaded;

    public function __construct() {

        $this->linkFormat = ini_get('xdebug.file_link_format');
    }

    /**
     * Render the profiler.
     *
     * @param   ProfilerInterface $profiler The profiler to render.
     *
     * @return  string  The rendered profiler.
     *
     * @since   1.0
     */
    public function render(ProfilerInterface $profiler) {

        $this->container = $profiler->getContainer();
        $ltext           = $this->container->get('language')
                                           ->getText();

        $html = array();

        // Some "mousewheel protecting" JS.
        $html[] = "<script>
require([\"jquery\",\"domReady!\"], function(\$) {
    \$('.dbg-header').on('click', function() {
        var \$e = \$('#'+\$(this).attr('rel'));
        \$e.toggle();
    });
});
</script>";

        $html[] = '<style>
#system-debug {
	background-color: #fff;
	color: #000;
	border: 1px dashed silver;
	padding: 10px;
	clear: both;
	z-index: 5000;
	position: relative;
}

#system-debug div.dbg-header {
	background-color: #ddd;
	border: 1px solid #eee;
	font-size: 16px;
}

#system-debug h3 {
	margin: 0;
}

#system-debug a h3 {
	background-color: #ddd;
	color: #000;
	font-size: 14px;
	padding: 5px;
	text-decoration: none;
	margin: 0px;
}

#system-debug .dbg-error a h3 {
	background-color: red;
}

#system-debug a:hover h3,
#system-debug a:focus h3 {
	background-color: #4d4d4d;
	color: #ddd;
	font-size: 14px;
	cursor: pointer;
	text-decoration: none;
}

#system-debug div.dbg-container {
	padding: 10px;
}

#system-debug span.dbg-command {
	color: blue;
	font-weight: bold;
}

#system-debug span.dbg-table {
	color: green;
	font-weight: bold;
}

#system-debug b.dbg-operator {
	color: red;
	font-weight: bold;
}

#system-debug h1 {
	background-color: #2c2c2c;
	color: #fff;
	padding: 10px;
	margin: 0;
	font-size: 16px;
	line-height: 1em;
}

#system-debug h4 {
	font-size: 14px;
	font-weight: bold;
	margin: 5px 0 0 0;
}

#system-debug h5 {
	font-size: 13px;
	font-weight: bold;
	margin: 5px 0 0 0;
}

div#system-debug {
	margin: 5px;
}

#system-debug ol {
	margin-left: 25px;
	margin-right: 25px;
	text-align: left;
	direction: ltr;
}

#system-debug ul {
	list-style: none;
	text-align: left;
	direction: ltr;
}

#system-debug li {
	font-size: 13px;
	margin-bottom: 10px;
}

#system-debug code {
	font-size: 13px;
	text-align: left;
	direction: ltr;
}

#system-debug p {
	font-size: 13px;
}

#system-debug div.dbg-header.dbg-error {
	background-color: red;
}
#system-debug .dbg-warning {
	color: red;
	font-weight: bold;
	background-color: #ffffcc !important;
}

#system-debug .accordion {
	margin-bottom: 0;
}
#system-debug .dbg-noprofile {
	text-decoration: line-through;
}

/* dbg-bars */
#system-debug .alert,
#system-debug .dbg-bars {
	margin-bottom: 10px;
}
#system-debug .dbg-bar-spacer {
	float: left;
	height: 100%;
}
/* dbg-bars-query */
#system-debug .dbg-bars-query .dbg-bar {
	opacity: 0.3;
}
#system-debug .dbg-bars-query:hover .dbg-bar {
	opacity: 0.6;
}
#system-debug .dbg-bars-query .dbg-bar:hover,
#system-debug .dbg-bars-query .dbg-bar-active,
#system-debug .dbg-bars-query:hover .dbg-bar-active {
	opacity: 1;
}


#system-debug table.dbg-query-table {
	margin: 0px 0px 6px;
}
#system-debug table.dbg-query-table th,
#system-debug table.dbg-query-table td {
	padding: 3px 8px;
}

#system-debug .dbg-profile-list .label {
	display: inline-block;
	min-width: 60px;
	text-align: right;
}

#system-debug .dbg-query-memory,
#system-debug .dbg-query-rowsnumber
{
	margin-left: 50px;
}
#dbg_container_session pre
{
	background: white;
	border: 0;
	margin: 0;
	padding: 0;
}
#dbg_container_session pre .blue
{
	color:blue;
}
#dbg_container_session pre .green
{
	color:green;
}
#dbg_container_session pre .black
{
	color:black;
}
#dbg_container_session pre .grey
{
	color:grey;
}
</style>';

        $html[] = '<div id="system-debug" class="profiler">';

        $html[] = '<h1>' . $ltext->translate('DEBUG_TITLE') . '</h1>';

        $html[] = $this->display('session', $profiler);

        $html[] = $this->display('profile_information', $profiler);

        $html[] = $this->display('memory_usage', $profiler);

        $html[] = $this->display('queries', $profiler);

        $html[] = '</div>';

        return implode("\n", $html);

    }

    /**
     * General display method.
     *
     * @param   string $item   The item to display.
     * @param   array  $errors Errors occured during execution.
     *
     * @return  string
     *
     * @since   2.5
     */
    protected function display($item, $profiler, array $errors = array()) {

        $ltext = $this->container->get('language')
                                 ->getText();

        $title = $ltext->translate('DEBUG_' . strtoupper($item));

        $status = '';

        if (count($errors)) {
            $status = ' dbg-error';
        }

        $fncName = 'display' . ucfirst(str_replace('_', '', $item));

        if (!method_exists($this, $fncName)) {
            return __METHOD__ . ' -- Unknown method: ' . $fncName . '<br />';
        }

        $html = '';

        $class = 'dbg-header ' . $status;

        $html[] = '<div class="' . $class . '" rel="dbg_container_' . $item . '"><a href="javascript:void(0);"><h3>' . $title . '</h3></a></div>';

        // @todo set with js.. ?
        $style = $profiler->dont_hide ? '' : ' style="display: none;"';

        $html[] = '<div ' . $style . ' class="dbg-container" id="dbg_container_' . $item . '">';
        $html[] = $this->$fncName($profiler);
        $html[] = '</div>';

        return implode('', $html);
    }

    /**
     * Display session information.
     *
     * @return  string
     */
    protected function displaySession($profiler) {

        $session = $this->container->get('session')
                                   ->all();

        return '<pre>' . $this->prettyPrintJSON($session) . '</pre>' . PHP_EOL;
    }

    /**
     * Display profile information.
     *
     * @param ProfilerInterface $profiler
     *
     * @return  string
     */
    protected function displayProfileInformation($profiler) {

        $html  = array();
        $ltext = $this->container->get('language')
                                 ->getText();

        $htmlMarks = array();

        $totalTime = 0;
        $totalMem  = 0;
        $marks     = array();
        /** @var \Joomla\Profiler\ProfilePointInterface $lastPoint * */
        $lastMark = null;

        foreach ($profiler->getPoints() as $mark) {

            $totalTime = $mark->getTime();
            $totalMem = $mark->getMemoryMegaBytes();

            $previousTime = $lastMark ? $lastMark->getTime() : 0.0;
            $previousMem  = $lastMark ? $lastMark->getMemoryMegaBytes() : 0;

            $time   = $mark->getTime() - $previousTime;
            $memory = $mark->getMemoryMegaBytes() - $previousMem;

            $htmlMark = sprintf($ltext->translate('DEBUG_TIME') . ': <span class="label label-time">%.2f&nbsp;ms</span> / <span class="label label-default">%.2f&nbsp;ms</span>' . ' ' . $ltext->translate('DEBUG_MEMORY') . ': <span class="label label-memory">%0.3f MB</span> / <span class="label label-default">%0.2f MB</span>' . ' %s: %s', $time * 1000, $mark->getTime() * 1000, $memory, $mark->getMemoryMegaBytes(), $profiler->getName(), $mark->getName());

            $marks[] = (object)array(
                'time'   => $time,
                'memory' => $memory,
                'html'   => $htmlMark,
                'tip'    => $mark->getName()
            );

            $lastMark = $mark;
        }

        $avgTime = $totalTime / count($marks);
        $avgMem  = $totalMem / count($marks);

        foreach ($marks as $mark) {
            if ($mark->time > $avgTime * 1.5) {
                $barClass   = 'bar-danger';
                $labelClass = 'label-danger label-danger';
            } elseif ($mark->time < $avgTime / 1.5) {
                $barClass   = 'bar-success';
                $labelClass = 'label-success';
            } else {
                $barClass   = 'bar-warning';
                $labelClass = 'label-warning';
            }

            if ($mark->memory > $avgMem * 1.5) {
                $barClassMem   = 'bar-danger';
                $labelClassMem = 'label-danger label-danger';
            } elseif ($mark->memory < $avgMem / 1.5) {
                $barClassMem   = 'bar-success';
                $labelClassMem = 'label-success';
            } else {
                $barClassMem   = 'bar-warning';
                $labelClassMem = 'label-warning';
            }

            $barClass .= " progress-$barClass";
            $barClassMem .= " progress-$barClassMem";

            $bars[] = (object)array(
                'width' => round($mark->time / ($totalTime / 100), 4),
                'class' => $barClass,
                'tip'   => $mark->tip . ' ' . round($mark->time, 2) . ' ms'
            );

            $barsMem[] = (object)array(
                'width' => round($mark->memory / ($totalMem / 100), 4),
                'class' => $barClassMem,
                'tip'   => $mark->tip . ' ' . round($mark->memory, 3) . '  MB',
            );

            $htmlMarks[] = '<div>' . str_replace('label-time', $labelClass, str_replace('label-memory', $labelClassMem, $mark->html)) . '</div>';
        }

        $html[] = '<h4>' . $ltext->translate('DEBUG_TIME') . '</h4>';
        $html[] = $this->renderBars($bars, 'profile');
        $html[] = '<h4>' . $ltext->translate('DEBUG_MEMORY') . '</h4>';
        $html[] = $this->renderBars($barsMem, 'profile');

        $html[] = '<div class="dbg-profile-list">' . implode('', $htmlMarks) . '</div>';

        $db  = $this->container->get('db');
        $log = $db->getLog();

        if ($log) {
            $timings = $db->getTimings();

            if ($timings) {
                $totalQueryTime = 0.0;
                $lastStart      = null;

                foreach ($timings as $k => $v) {
                    if (!($k % 2)) {
                        $lastStart = $v;
                    } else {
                        $totalQueryTime += $v - $lastStart;
                    }
                }

                $totalQueryTime = $totalQueryTime * 1000;

                if ($totalQueryTime > ($totalTime * 1000 * 0.25)) {
                    $labelClass = 'label-danger';
                } elseif ($totalQueryTime < ($totalTime * 1000 * 0.15)) {
                    $labelClass = 'label-success';
                } else {
                    $labelClass = 'label-warning';
                }

                $html[] = '<br /><div>' . $ltext->sprintf('DEBUG_QUERIES_TIME', sprintf('<span class="label ' . $labelClass . '">%.2f&nbsp;ms</span>', $totalQueryTime)) . '</div>';
            }
        }

        return implode('', $html);
    }

    protected function displayQueries($profiler) {

        $db  = $this->container->get('db');
        $log = $db->getLog();

	    $ltext = $this->container->get('language')
	                             ->getText();

        if (!$log) {
            return $ltext->translate('DEBUG_NO_QUERIES');
        }

        $timings    = $db->getTimings();
        $callStacks = $db->getCallStacks();

        $db->setDebug(false);

        $selectQueryTypeTicker = array();
        $otherQueryTypeTicker  = array();

        $timing             = [];
        $explains           = [];
        $sqlShowProfiles    = [];
        $sqlShowProfileEach = [];
        $maxtime            = 0;

        if (isset($timings[0])) {
            $startTime         = $timings[0];
            $endTime           = $timings[count($timings) - 1];
            $totalBargraphTime = $endTime - $startTime;

            if ($totalBargraphTime > 0) {
                foreach ($log as $id => $query) {
                    if (isset($timings[$id * 2 + 1])) {
                        // Compute the query time: $timing[$k] = array( queryTime, timeBetweenQueries ).
                        $timing[$id] = array(
                            ($timings[$id * 2 + 1] - $timings[$id * 2]) * 1000,
                            $id > 0 ? ($timings[$id * 2] - $timings[$id * 2 - 1]) * 1000 : 0
                        );
                        $maxtime     = max($maxtime, $timing[$id]['0']);
                    }
                }
            }
        } else {
            $startTime         = null;
            $totalBargraphTime = 1;
        }

        $bars           = array();
        $info           = array();
        $totalQueryTime = 0;
        $duplicates     = array();

        $dbVersion5037 = (strpos($db->getName(), 'mysql') !== false) && version_compare($db->getVersion(), '5.0.37', '>=');
        $dbVersion55   = (strpos($db->getName(), 'mysql') !== false) && version_compare($db->getVersion(), '5.5', '>=');
        $dbVersion56   = (strpos($db->getName(), 'mysql') !== false) && version_compare($db->getVersion(), '5.6', '>=');

        if ($dbVersion5037) {
            try {

                // Check if profiling is enabled.
                $db->setQuery("SHOW VARIABLES LIKE 'have_profiling'");
                $hasProfiling = $db->loadResult();

                if ($hasProfiling) {
                    // Run a SHOW PROFILE query.
                    $db->setQuery('SHOW PROFILES');
                    $sqlShowProfiles = $db->loadAssocList();

                    if ($sqlShowProfiles) {
                        foreach ($sqlShowProfiles as $qn) {
                            // Run SHOW PROFILE FOR QUERY for each query where a profile is available (max 100).
                            $db->setQuery('SHOW PROFILE FOR QUERY ' . (int)($qn['Query_ID']));
                            $sqlShowProfileEach[(int)($qn['Query_ID'] - 1)] = $db->loadAssocList();
                        }
                    }
                } else {
                    $sqlShowProfileEach[0] = array(array('Error' => 'MySql have_profiling = off'));
                }
            } catch (\Exception $e) {
                $sqlShowProfileEach[0] = array(array('Error' => $e->getMessage()));
            }
        }

        foreach ($log as $id => $query) {
            $did = md5($query);

            if (!isset($duplicates[$did])) {
                $duplicates[$did] = array();
            }

            $duplicates[$did][] = $id;

            if ((stripos($query, 'select') === 0) || ($dbVersion56 && ((stripos($query, 'delete') === 0) || (stripos($query, 'update') === 0)))) {
                try {
                    $db->setQuery('EXPLAIN ' . (($dbVersion56 || $dbVersion55) ? 'EXTENDED ' : '') . $query);
                    $explains[$id] = $db->loadAssocList();
                } catch (\Exception $e) {
                    $explains[$id] = array(array('Error' => $e->getMessage()));
                }
            }

            if ($timings && isset($timings[$id * 2 + 1])) {
                // Compute the query time.
                $queryTime = ($timings[$id * 2 + 1] - $timings[$id * 2]) * 1000;
                $totalQueryTime += $queryTime;

                // Run an EXPLAIN EXTENDED query on the SQL query if possible.
                $hasWarnings          = false;
                $hasWarningsInProfile = false;

                if (isset($explains[$id])) {
                    $explain = $this->tableToHtml($explains[$id], $hasWarnings);
                } else {
                    $explain = $ltext->sprintf('DEBUG_QUERY_EXPLAIN_NOT_POSSIBLE', htmlspecialchars($query));
                }

                // Run a SHOW PROFILE query.
                $profile = '';

                if (in_array($db->getName(), array(
                    'mysqli',
                    'profiledmysqli',
                    'mysql',
                    'pdomysql'
                ))) {
                    if (isset($sqlShowProfileEach[$id])) {
                        $profileTable = $sqlShowProfileEach[$id];
                        $profile      = $this->tableToHtml($profileTable, $hasWarningsInProfile);
                    }
                }

                // How heavy should the string length count: 0 - 1.
                $ratio     = 0.5;
                $timeScore = $queryTime / ((strlen($query) + 1) * $ratio) * 200;

                // Determine color of bargraph depending on query speed and presence of warnings in EXPLAIN.
                if ($timeScore > 10) {
                    $barClass   = 'bar-danger';
                    $labelClass = 'label-danger';
                } elseif ($hasWarnings || $timeScore > 5) {
                    $barClass   = 'bar-warning';
                    $labelClass = 'label-warning';
                } else {
                    $barClass   = 'bar-success';
                    $labelClass = 'label-success';
                }

                // Computes bargraph as follows: Position begin and end of the bar relatively to whole execution time.
                $prevBar = ($id && isset($bars[$id - 1])) ? $bars[$id - 1] : 0;

                $barPre   = round($timing[$id][1] / ($totalBargraphTime * 10), 4);
                $barWidth = round($timing[$id][0] / ($totalBargraphTime * 10), 4);
                $minWidth = 0.3;

                if ($barWidth < $minWidth) {
                    $barPre -= ($minWidth - $barWidth);

                    if ($barPre < 0) {
                        $minWidth += $barPre;
                        $barPre = 0;
                    }

                    $barWidth = $minWidth;
                }

                $bars[$id] = (object)array(
                    'class' => $barClass,
                    'width' => $barWidth,
                    'pre'   => $barPre,
                    'tip'   => sprintf('%.2f&nbsp;ms', $queryTime)
                );
                $info[$id] = (object)array(
                    'class'       => $labelClass,
                    'explain'     => $explain,
                    'profile'     => $profile,
                    'hasWarnings' => $hasWarnings
                );
            }
        }

        // Remove single queries from $duplicates.
        $total_duplicates = 0;

        foreach ($duplicates as $did => $dups) {
            if (count($dups) < 2) {
                unset($duplicates[$did]);
            } else {
                $total_duplicates += count($dups);
            }
        }

        // Fix first bar width.
        $minWidth = 0.3;

        if ($bars[0]->width < $minWidth && isset($bars[1])) {
            $bars[1]->pre -= ($minWidth - $bars[0]->width);

            if ($bars[1]->pre < 0) {
                $minWidth += $bars[1]->pre;
                $bars[1]->pre = 0;
            }

            $bars[0]->width = $minWidth;
        }

        $memoryUsageNow = memory_get_usage();
        $list           = array();

        foreach ($log as $id => $query) {
            // Start query type ticker additions.
            $fromStart  = stripos($query, 'from');
            $whereStart = stripos($query, 'where', $fromStart);

            if ($whereStart === false) {
                $whereStart = stripos($query, 'order by', $fromStart);
            }

            if ($whereStart === false) {
                $whereStart = strlen($query) - 1;
            }

            $fromString = substr($query, 0, $whereStart);
            $fromString = str_replace("\t", " ", $fromString);
            $fromString = str_replace("\n", " ", $fromString);
            $fromString = trim($fromString);

            // Initialise the select/other query type counts the first time.
            if (!isset($selectQueryTypeTicker[$fromString])) {
                $selectQueryTypeTicker[$fromString] = 0;
            }

            if (!isset($otherQueryTypeTicker[$fromString])) {
                $otherQueryTypeTicker[$fromString] = 0;
            }

            // Increment the count.
            if (stripos($query, 'select') === 0) {
                $selectQueryTypeTicker[$fromString] = $selectQueryTypeTicker[$fromString] + 1;
                unset($otherQueryTypeTicker[$fromString]);
            } else {
                $otherQueryTypeTicker[$fromString] = $otherQueryTypeTicker[$fromString] + 1;
                unset($selectQueryTypeTicker[$fromString]);
            }

            $text = $this->highlightQuery($query);

            if ($timings && isset($timings[$id * 2 + 1])) {
                // Compute the query time.
                $queryTime = ($timings[$id * 2 + 1] - $timings[$id * 2]) * 1000;

                // Timing
                // Formats the output for the query time with EXPLAIN query results as tooltip:
                $htmlTiming = '<div style="margin: 0 0 5px;"><span class="dbg-query-time">';
                $htmlTiming .= $ltext->sprintf('DEBUG_QUERY_TIME', sprintf('<span class="label %s">%.2f&nbsp;ms</span>', $info[$id]->class, $timing[$id]['0']));

                if ($timing[$id]['1']) {
                    $htmlTiming .= ' ' . $ltext->sprintf('DEBUG_QUERY_AFTER_LAST', sprintf('<span class="label label-default">%.2f&nbsp;ms</span>', $timing[$id]['1']));
                }

                $htmlTiming .= '</span>';

                if (isset($callStacks[$id][0]['memory'])) {
                    $memoryUsed        = $callStacks[$id][0]['memory'][1] - $callStacks[$id][0]['memory'][0];
                    $memoryBeforeQuery = $callStacks[$id][0]['memory'][0];

                    // Determine colour of query memory usage.
                    if ($memoryUsed > 0.1 * $memoryUsageNow) {
                        $labelClass = 'label-danger';
                    } elseif ($memoryUsed > 0.05 * $memoryUsageNow) {
                        $labelClass = 'label-warning';
                    } else {
                        $labelClass = 'label-success';
                    }

                    $htmlTiming .= ' ' . '<span class="dbg-query-memory">' . $ltext->sprintf('DEBUG_MEMORY_USED_FOR_QUERY', sprintf('<span class="label ' . $labelClass . '">%.3f&nbsp;MB</span>', $memoryUsed / 1048576), sprintf('<span class="label label-default">%.3f&nbsp;MB</span>', $memoryBeforeQuery / 1048576)) . '</span>';

                    if ($callStacks[$id][0]['memory'][2] !== null) {
                        // Determine colour of number or results.
                        $resultsReturned = $callStacks[$id][0]['memory'][2];

                        if ($resultsReturned > 3000) {
                            $labelClass = 'label-danger';
                        } elseif ($resultsReturned > 1000) {
                            $labelClass = 'label-warning';
                        } elseif ($resultsReturned == 0) {
                            $labelClass = '';
                        } else {
                            $labelClass = 'label-success';
                        }

                        $htmlResultsReturned = '<span class="label ' . $labelClass . '">' . (int)$resultsReturned . '</span>';
                        $htmlTiming .= ' <span class="dbg-query-rowsnumber">' . $ltext->sprintf('DEBUG_ROWS_RETURNED_BY_QUERY', $htmlResultsReturned) . '</span>';
                    }
                }

                $htmlTiming .= '</div>';

                // Bar.
                $htmlBar = $this->renderBars($bars, 'query', $id);

                // Profile query.
                $title = $ltext->translate('DEBUG_PROFILE');

                if (!$info[$id]->profile) {
                    $title = '<span class="dbg-noprofile">' . $title . '</span>';
                }

                $htmlProfile = ($info[$id]->profile ? $info[$id]->profile : $ltext->translate('DEBUG_NO_PROFILE'));

                $htmlAccordions = $this->startAccordion('dbg_query_' . $id, array(
                    'active' => ($info[$id]->hasWarnings ? ('dbg_query_explain_' . $id) : '')
                ));

                $htmlAccordions .= $this->addSlide('dbg_query_' . $id, $ltext->translate('DEBUG_EXPLAIN'), 'dbg_query_explain_' . $id) . $info[$id]->explain . $this->endSlide();

                $htmlAccordions .= $this->addSlide('dbg_query_' . $id, $title, 'dbg_query_profile_' . $id) . $htmlProfile . $this->endSlide();

                // Call stack and back trace.
                if (isset($callStacks[$id])) {
                    $htmlAccordions .= $this->addSlide('dbg_query_' . $id, $ltext->translate('DEBUG_CALL_STACK'), 'dbg_query_callstack_' . $id) . $this->renderCallStack($callStacks[$id]) . $this->endSlide();
                }

                $htmlAccordions .= $this->endAccordion();

                $did = md5($query);

                if (isset($duplicates[$did])) {
                    $dups = array();

                    foreach ($duplicates[$did] as $dup) {
                        if ($dup != $id) {
                            $dups[] = '<a href="#dbg-query-' . ($dup + 1) . '">#' . ($dup + 1) . '</a>';
                        }
                    }

                    $htmlQuery = '<div class="alert alert-error">' . $ltext->translate('DEBUG_QUERY_DUPLICATES') . ': ' . implode('&nbsp; ', $dups) . '</div>' . '<pre class="alert hasTooltip" title="' . $ltext->translate('DEBUG_QUERY_DUPLICATES_FOUND') . '">' . $text . '</pre>';
                } else {
                    $htmlQuery = '<pre>' . $text . '</pre>';
                }

                $list[] = '<a name="dbg-query-' . ($id + 1) . '"></a>' . $htmlTiming . $htmlBar . $htmlQuery . $htmlAccordions;
            } else {
                $list[] = '<pre>' . $text . '</pre>';
            }
        }

        $totalTime = 0;

        foreach ($profiler->getPoints() as $mark) {
            $totalTime += $mark->getTime();
        }

        if ($totalQueryTime > ($totalTime * 0.25)) {
            $labelClass = 'label-danger';
        } elseif ($totalQueryTime < ($totalTime * 0.15)) {
            $labelClass = 'label-success';
        } else {
            $labelClass = 'label-warning';
        }

        if ($this->totalQueries == 0) {
            $this->totalQueries = $db->getCount();
        }

        $html = array();

        $html[] = '<h4>' . $ltext->sprintf('DEBUG_QUERIES_LOGGED', $this->totalQueries) . sprintf(' <span class="label ' . $labelClass . '">%.2f&nbsp;ms</span>', ($totalQueryTime)) . '</h4><br />';

        if ($total_duplicates) {
            $html[] = '<div class="alert alert-error">' . '<h4>' . $ltext->sprintf('DEBUG_QUERY_DUPLICATES_TOTAL_NUMBER', $total_duplicates) . '</h4>';

            foreach ($duplicates as $dups) {
                $links = array();

                foreach ($dups as $dup) {
                    $links[] = '<a href="#dbg-query-' . ($dup + 1) . '">#' . ($dup + 1) . '</a>';
                }

                $html[] = '<div>' . $ltext->sprintf('DEBUG_QUERY_DUPLICATES_NUMBER', count($links)) . ': ' . implode('&nbsp; ', $links) . '</div>';
            }

            $html[] = '</div>';
        }

        $html[] = '<ol><li>' . implode('<hr /></li><li>', $list) . '<hr /></li></ol>';

        // Get the totals for the query types.
        $totalSelectQueryTypes = count($selectQueryTypeTicker);
        $totalOtherQueryTypes  = count($otherQueryTypeTicker);
        $totalQueryTypes       = $totalSelectQueryTypes + $totalOtherQueryTypes;

        $html[] = '<h4>' . $ltext->sprintf('DEBUG_QUERY_TYPES_LOGGED', $totalQueryTypes) . '</h4>';

        if ($totalSelectQueryTypes) {
            $html[] = '<h5>' . $ltext->translate('DEBUG_SELECT_QUERIES') . '</h5>';

            arsort($selectQueryTypeTicker);

            $list = array();

            foreach ($selectQueryTypeTicker as $query => $occurrences) {
                $list[] = '<pre>' . $ltext->sprintf('DEBUG_QUERY_TYPE_AND_OCCURRENCES', $this->highlightQuery($query), $occurrences) . '</pre>';
            }

            $html[] = '<ol><li>' . implode('</li><li>', $list) . '</li></ol>';
        }

        if ($totalOtherQueryTypes) {
            $html[] = '<h5>' . $ltext->translate('DEBUG_OTHER_QUERIES') . '</h5>';

            arsort($otherQueryTypeTicker);

            $list = array();

            foreach ($otherQueryTypeTicker as $query => $occurrences) {
                $list[] = '<pre>' . $ltext->sprintf('DEBUG_QUERY_TYPE_AND_OCCURRENCES', $this->highlightQuery($query), $occurrences) . '</pre>';
            }

            $html[] = '<ol><li>' . implode('</li><li>', $list) . '</li></ol>';
        }

        return implode('', $html);

    }

    /**
     * Display memory usage.
     *
     * @return  string
     *
     * @since   2.5
     */
    protected function displayMemoryUsage($profiler) {

        $ltext = $this->container->get('language')->getText();

        $bytes = memory_get_usage();

        return '<span class="label label-default">' . sprintf('%.3f&nbsp;MB', $bytes / 1048576) . '</span>' . ' (<span class="label label-default">' . number_format($bytes, 0, ",", " ") . ' ' . $ltext->translate('DEBUG_BYTES') . '</span>)';
    }

    /**
     * Render the bars.
     *
     * @param   array   &$bars Array of bar data
     * @param   string  $class Optional class for items
     * @param   integer $id    Id if the bar to highlight
     *
     * @return  string
     *
     * @since   3.1.2
     */
    protected function renderBars(&$bars, $class = '', $id = null) {

        $html = array();

        foreach ($bars as $i => $bar) {
            if (isset($bar->pre) && $bar->pre) {
                $html[] = '<div class="dbg-bar-spacer" style="width:' . $bar->pre . '%;"></div>';
            }

            $barClass = trim('bar dbg-bar progress-bar ' . (isset($bar->class) ? $bar->class : ''));

            if ($id !== null && $i == $id) {
                $barClass .= ' dbg-bar-active';
            }

            $tip = '';

            if (isset($bar->tip) && $bar->tip) {
                $barClass .= ' hasTooltip';
                $tip = $bar->tip;
            }

            $html[] = '<div class="bar dbg-bar ' . $barClass . '" title="' . $tip . '" style="width: ' . $bar->width . '%;" href="#dbg-' . $class . '-' . ($i + 1) . '"></div>';
        }

        return '<div class="progress dbg-bars dbg-bars-' . $class . '">' . implode('', $html) . '</div>';
    }

    /**
     * Render an HTML table based on a multi-dimensional array.
     *
     * @param   array   $table        An array of tabular data.
     * @param   boolean &$hasWarnings Changes value to true if warnings are displayed, otherwise untouched
     *
     * @return  string
     *
     * @since   3.1.2
     */
    protected function tableToHtml($table, &$hasWarnings) {

        if (!$table) {
            return null;
        }

        $ltext = $this->container->get('language')
                                 ->getText();

        $html = array();

        $html[] = '<table class="table table-striped dbg-query-table"><tr>';

        foreach (array_keys($table[0]) as $k) {
            $html[] = '<th>' . htmlspecialchars($k) . '</th>';
        }

        $html[] = '</tr>';

        $durations = array();

        foreach ($table as $tr) {
            if (isset($tr['Duration'])) {
                $durations[] = $tr['Duration'];
            }
        }

        rsort($durations, SORT_NUMERIC);

        foreach ($table as $tr) {
            $html[] = '<tr>';

            foreach ($tr as $k => $td) {
                if ($td === null) {
                    // Display null's as 'NULL'.
                    $td = 'NULL';
                }

                // Treat special columns.
                if ($k == 'Duration') {
                    if ($td >= 0.001 && ($td == $durations[0] || (isset($durations[1]) && $td == $durations[1]))) {
                        // Duration column with duration value of more than 1 ms and within 2 top duration in SQL engine: Highlight warning.
                        $html[]      = '<td class="dbg-warning">';
                        $hasWarnings = true;
                    } else {
                        $html[] = '<td>';
                    }

                    // Display duration in milliseconds with the unit instead of seconds.
                    $html[] = sprintf('%.2f&nbsp;ms', $td * 1000);
                } elseif ($k == 'Error') {
                    // An error in the EXPLAIN query occured, display it instead of the result (means original query had syntax error most probably).
                    $html[]      = '<td class="dbg-warning">' . htmlspecialchars($td);
                    $hasWarnings = true;
                } elseif ($k == 'key') {
                    if ($td === 'NULL') {
                        // Displays query parts which don't use a key with warning:
                        $html[]      = '<td><strong>' . '<span class="dbg-warning hasTooltip" title="' . $ltext->translate('DEBUG_WARNING_NO_INDEX_DESC') . '">' . $ltext->translate('DEBUG_WARNING_NO_INDEX') . '</span>' . '</strong>';
                        $hasWarnings = true;
                    } else {
                        $html[] = '<td><strong>' . htmlspecialchars($td) . '</strong>';
                    }
                } elseif ($k == 'Extra') {
                    $htmlTd = htmlspecialchars($td);

                    // Replace spaces with &nbsp; (non-breaking spaces) for less tall tables displayed.
                    $htmlTd = preg_replace('/([^;]) /', '\1&nbsp;', $htmlTd);

                    // Displays warnings for "Using filesort":
                    $htmlTdWithWarnings = str_replace('Using&nbsp;filesort', '<span class="dbg-warning hasTooltip" title="' . $ltext->translate('DEBUG_WARNING_USING_FILESORT_DESC') . '">' . $ltext->translate('DEBUG_WARNING_USING_FILESORT') . '</span>', $htmlTd);

                    if ($htmlTdWithWarnings !== $htmlTd) {
                        $hasWarnings = true;
                    }

                    $html[] = '<td>' . $htmlTdWithWarnings;
                } else {
                    $html[] = '<td>' . htmlspecialchars($td);
                }

                $html[] = '</td>';
            }

            $html[] = '</tr>';
        }

        $html[] = '</table>';

        return implode('', $html);
    }

    /**
     * Simple highlight for SQL queries.
     *
     * @param   string $query The query to highlight.
     *
     * @return  string
     *
     * @since   2.5
     */
    protected function highlightQuery($query) {

        $newlineKeywords = '#\b(FROM|LEFT|INNER|OUTER|WHERE|SET|VALUES|ORDER|GROUP|HAVING|LIMIT|ON|AND|CASE)\b#i';

        $query = htmlspecialchars($query, ENT_QUOTES);

        $query = preg_replace($newlineKeywords, '<br />&#160;&#160;\\0', $query);

        $regex = array(

            // Tables are identified by the prefix.
            '/(=)/'                                                          => '<b class="dbg-operator">$1</b>',

            // All uppercase words have a special meaning.
            '/(?<!\w|>)([A-Z_]{2,})(?!\w)/x'                                 => '<span class="dbg-command">$1</span>',

            // Tables are identified by the prefix.
            '/(' . $this->container->get('config')
                                   ->get('database.prefix') . '[a-z_0-9]+)/' => '<span class="dbg-table">$1</span>'

        );

        $query = preg_replace(array_keys($regex), array_values($regex), $query);

        $query = str_replace('*', '<b style="color: red;">*</b>', $query);

        return $query;
    }

    /**
     * Render the backtrace.
     *
     * Stolen from JError to prevent it's removal.
     *
     * @param   Exception $error The Exception object to be rendered.
     *
     * @return  string     Rendered backtrace.
     *
     * @since   2.5
     */
    protected function renderBacktrace($error) {

        $backtrace = $error->getTrace();

        $html = array();

        if (is_array($backtrace)) {
            $j = 1;

            $html[] = '<table cellpadding="0" cellspacing="0">';

            $html[] = '<tr>';
            $html[] = '<td colspan="3"><strong>Call stack</strong></td>';
            $html[] = '</tr>';

            $html[] = '<tr>';
            $html[] = '<th>#</th>';
            $html[] = '<th>Function</th>';
            $html[] = '<th>Location</th>';
            $html[] = '</tr>';

            for ($i = count($backtrace) - 1; $i >= 0; $i--) {
                $link = '&#160;';

                if (isset($backtrace[$i]['file'])) {
                    $link = $this->formatLink($backtrace[$i]['file'], $backtrace[$i]['line']);
                }

                $html[] = '<tr>';
                $html[] = '<td>' . $j . '</td>';

                if (isset($backtrace[$i]['class'])) {
                    $html[] = '<td>' . $backtrace[$i]['class'] . $backtrace[$i]['type'] . $backtrace[$i]['function'] . '()</td>';
                } else {
                    $html[] = '<td>' . $backtrace[$i]['function'] . '()</td>';
                }

                $html[] = '<td>' . $link . '</td>';

                $html[] = '</tr>';
                $j++;
            }

            $html[] = '</table>';
        }

        return implode('', $html);
    }

    /**
     * Replaces the Joomla! root with "JROOT" to improve readability.
     * Formats a link with a special value xdebug.file_link_format
     * from the php.ini file.
     *
     * @param   string $file The full path to the file.
     * @param   string $line The line number.
     *
     * @return  string
     *
     * @since   2.5
     */
    protected function formatLink($file, $line = '') {

        $link = str_replace(JPATH_ROOT, 'JROOT', $file);
        $link .= ($line) ? ':' . $line : '';

        if ($this->linkFormat) {
            $href = $this->linkFormat;
            $href = str_replace('%f', $file, $href);
            $href = str_replace('%l', $line, $href);

            $html = '<a href="' . $href . '">' . $link . '</a>';
        } else {
            $html = $link;
        }

        return $html;
    }

    /**
     * Add javascript support for Bootstrap accordians and insert the accordian
     *
     * @param   string $selector                       The ID selector for the tooltip.
     * @param   array  $params                         An array of options for the tooltip.
     *                                                 Options for the tooltip can be:
     *                                                 - parent  selector  If selector then all collapsible elements under the specified parent will be closed when this
     *                                                 collapsible item is shown. (similar to traditional accordion behavior)
     *                                                 - toggle  boolean   Toggles the collapsible element on invocation
     *                                                 - active  string    Sets the active slide during load
     *
     *                             - onShow    function  This event fires immediately when the show instance method is called.
     *                             - onShown   function  This event is fired when a collapse element has been made visible to the user
     *                                                   (will wait for css transitions to complete).
     *                             - onHide    function  This event is fired immediately when the hide method has been called.
     *                             - onHidden  function  This event is fired when a collapse element has been hidden from the user
     *                                                   (will wait for css transitions to complete).
     *
     * @return  string  HTML for the accordian
     *
     * @since   3.0
     */
    protected function startAccordion($selector = 'myAccordian', $params = array()) {

        if (!isset(static::$loaded[__METHOD__][$selector])) {

            // Setup options object
            $opt['parent'] = isset($params['parent']) ? ($params['parent'] == true ? '#' . $selector : $params['parent']) : false;
            $opt['toggle'] = isset($params['toggle']) ? (boolean)$params['toggle'] : ($opt['parent'] === false || isset($params['active']) ? false : true);
            $onShow        = isset($params['onShow']) ? (string)$params['onShow'] : null;
            $onShown       = isset($params['onShown']) ? (string)$params['onShown'] : null;
            $onHide        = isset($params['onHide']) ? (string)$params['onHide'] : null;
            $onHidden      = isset($params['onHidden']) ? (string)$params['onHidden'] : null;

            $options = json_encode($opt);

            $opt['active'] = isset($params['active']) ? (string)$params['active'] : '';

            // Build the script.
            $script   = array();
            $script[] = "\t$('#" . $selector . "').collapse(" . $options . ")";

            if ($onShow) {
                $script[] = "\t.on('show', " . $onShow . ")";
            }

            if ($onShown) {
                $script[] = "\t.on('shown', " . $onShown . ")";
            }

            if ($onHide) {
                $script[] = "\t.on('hideme', " . $onHide . ")";
            }

            if ($onHidden) {
                $script[] = "\t.on('hidden', " . $onHidden . ")";
            }

            $html = [];

            $html[] = "<script>
require([\"jquery\",\"bootstrap\",\"domReady!\"], function(\$) {" . implode("\n", $script) . "});
</script>";

            // Set static array
            static::$loaded[__METHOD__][$selector] = $opt;

            $html[] = '<div id="' . $selector . '" class="accordion">';

            return implode("\n", $html);
        }
    }

    /**
     * Close the current accordion
     *
     * @return  string  HTML to close the accordian
     *
     * @since   3.0
     */
    public static function endAccordion() {

        return '</div>';
    }

    /**
     * Begins the display of a new accordion slide.
     *
     * @param   string $selector Identifier of the accordion group.
     * @param   string $text     Text to display.
     * @param   string $id       Identifier of the slide.
     * @param   string $class    Class of the accordion group.
     *
     * @return  string  HTML to add the slide
     *
     * @since   3.0
     */
    protected function addSlide($selector, $text, $id, $class = '') {

        $in     = (static::$loaded[__CLASS__ . '::startAccordion'][$selector]['active'] == $id) ? ' in' : '';
        $parent = static::$loaded[__CLASS__ . '::startAccordion'][$selector]['parent'] ? ' data-parent="' . static::$loaded[__CLASS__ . '::startAccordion'][$selector]['parent'] . '"' : '';
        $class  = (!empty($class)) ? ' ' . $class : '';

        $html = '<div class="accordion-group' . $class . '">' . '<div class="accordion-heading">' . '<strong><a href="#' . $id . '" data-toggle="collapse"' . $parent . ' class="accordion-toggle">' . $text . '</a></strong>' . '</div>' . '<div class="accordion-body collapse' . $in . '" id="' . $id . '">' . '<div class="accordion-inner">';

        return $html;
    }

    /**
     * Close the current slide
     *
     * @return  string  HTML to close the slide
     *
     * @since   3.0
     */
    protected function endSlide() {

        return '</div></div></div>';
    }

    /**
     * Renders call stack and back trace in HTML.
     *
     * @param   array $callStack The call stack and back trace array.
     *
     * @return  string  The call stack and back trace in HMTL format.
     *
     * @since   3.5
     */
    protected function renderCallStack(array $callStack = array()) {

        $htmlCallStack = '';

        $ltext = $this->container->get('language')
                                 ->getText();

        if (isset($callStack)) {
            $htmlCallStack .= '<div>';
            $htmlCallStack .= '<table class="table table-striped dbg-query-table">';
            $htmlCallStack .= '<thead>';
            $htmlCallStack .= '<th>#</th>';
            $htmlCallStack .= '<th>' . $ltext->translate('DEBUG_CALL_STACK_CALLER') . '</th>';
            $htmlCallStack .= '<th>' . $ltext->translate('DEBUG_CALL_STACK_FILE_AND_LINE') . '</th>';
            $htmlCallStack .= '</tr>';
            $htmlCallStack .= '</thead>';
            $htmlCallStack .= '<tbody>';

            $count = count($callStack);

            foreach ($callStack as $call) {
                // Dont' back trace log classes.
                if (isset($call['class']) && strpos($call['class'], 'JLog') !== false) {
                    $count--;
                    continue;
                }

                $htmlCallStack .= '<tr>';

                $htmlCallStack .= '<td>' . $count . '</td>';

                $htmlCallStack .= '<td>';
                if (isset($call['class'])) {
                    // If entry has Class/Method print it.
                    $htmlCallStack .= htmlspecialchars($call['class'] . $call['type'] . $call['function']) . '()';
                } else {
                    if (!empty($call['args'])) {
                        // If entry has args is a require/include.
                        $htmlCallStack .= htmlspecialchars($call['function']) . ' ' . $this->formatLink($call['args'][0]);
                    } else {
                        // It's a function.
                        $htmlCallStack .= htmlspecialchars($call['function']) . '()';
                    }
                }
                $htmlCallStack .= '</td>';

                $htmlCallStack .= '<td>';

                // If entry doesn't have line and number the next is a call_user_func.
                if (!isset($call['file']) && !isset($call['line'])) {
                    $htmlCallStack .= $ltext->translate('DEBUG_CALL_STACK_SAME_FILE');
                } // If entry has file and line print it.
                else {
                    $htmlCallStack .= $this->formatLink(htmlspecialchars($call['file']), htmlspecialchars($call['line']));
                }
                $htmlCallStack .= '</td>';

                $htmlCallStack .= '</tr>';
                $count--;
            }
            $htmlCallStack .= '</tbody>';
            $htmlCallStack .= '</table>';
            $htmlCallStack .= '</div>';

            if (!$this->linkFormat) {
                $htmlCallStack .= '<div>[<a href="https://xdebug.org/docs/all_settings#file_link_format" target="_blank">';
                $htmlCallStack .= $ltext->translate('DEBUG_LINK_FORMAT') . '</a>]</div>';
            }
        }

        return $htmlCallStack;
    }

    /**
     * Pretty print JSON with colors.
     *
     * @param   string $json The json raw string.
     *
     * @return  string  The json string pretty printed.
     *
     * @since   3.5
     */
    protected function prettyPrintJSON($json = '') {

        // In PHP 5.4.0 or later we have pretty print option.
        if (version_compare(PHP_VERSION, '5.4', '>=')) {
            $json = json_encode($json, JSON_PRETTY_PRINT);
        }

        // Add some colors
        $json = preg_replace('#"([^"]+)":#', '<span class=\'black\'>"</span><span class=\'green\'>$1</span><span class=\'black\'>"</span>:', $json);
        $json = preg_replace('#"(|[^"]+)"(\n|\r\n|,)#', '<span class=\'grey\'>"$1"</span>$2', $json);
        $json = str_replace('null,', '<span class=\'blue\'>null</span>,', $json);

        return $json;
    }

}
