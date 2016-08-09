<?php
/**
 * Part of the ETD Framework Profiler Package
 *
 * @copyright   Copyright (C) 2016 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Profiler;

use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Profiler\Profiler as JoomlaProfiler;

class Profiler extends JoomlaProfiler implements ContainerAwareInterface {

    use ContainerAwareTrait;

    public $dont_hide = false;

    public function dump($current_uri) {

        $html     = [];
        $time     = time();
        $base     = JPATH_LOGS . "/profiler_" . $this->getName() . "_" . $time;
        $filename = $base;
        $current  = 0;
        while (file_exists($filename . ".html")) {
            $current++;
            $filename = $base . "_" . $current;
        }

        $this->dont_hide = true;
        $content  = $this->render();

        $html[] = '<!DOCTYPE html>';
        $html[] = '<html>';
        $html[] = '<head>';
        $html[] = '<meta charset="utf-8">';
        $html[] = '<title>' . $this->getName() . ' Profiler Dump (' . date('d/m/Y H:i:s', $time) . ')</title>';
        $html[] = '</head>';
        $html[] = '<body>';
        $html[] = '<h1>' . $current_uri . '</h1>';
        $html[] = $content;
        $html[] = '</body>';
        $html[] = '</html>';

        return file_put_contents($filename . ".html", implode("\n", $html));

    }

}