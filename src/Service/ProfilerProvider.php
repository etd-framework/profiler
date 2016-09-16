<?php
/**
 * Part of the ETD Framework Service Package
 *
 * @copyright   Copyright (C) 2016 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Profiler\Service;

use EtdSolutions\Profiler\Profiler;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class ProfilerProvider implements ServiceProviderInterface {

    /**
     * Enregistre le fournisseur de service auprès du container DI.
     *
     * @param Container $container Le container DI.
     *
     * @return Container Retourne l'instance pour le chainage.
     */
    public function register(Container $container) {

        $config = $container->get('config');

        // On instancie le profiler si besoin.
        if ($config->get('profiler.enable', false)) {

            $container->set('Joomla\\Profiler\\ProfilerInterface', function () use ($config, $container) {

            	// Classe pour le renderer
            	$renderer_class = $config->get('profiler.renderer', '\\Joomla\\Profiler\\Renderer\\DefaultRenderer');

	            // On contrôle que la classe existe.
	            if (!class_exists($renderer_class)) {
		            throw new \InvalidArgumentException(sprintf('%s profiler renderer class does not exist', $renderer_class));
	            }

	            $renderer = new $renderer_class();

                $profiler = new Profiler($config->get('profiler.name', 'default'), $renderer, [], (bool) $config->get('profiler.memoryrealusage', false));
	            $profiler->setContainer($container);

                return $profiler;

            }, true, true);

            // On crée un alias pour le profiler.
            $container->alias('profiler', 'Joomla\\Profiler\\ProfilerInterface');

        }
    }
}
