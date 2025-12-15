<?php 
namespace OSW3\Api\Resolver;

final class ResponseResolver
{
    public static function execute(array &$config): array 
    {
        foreach ($config['providers'] as &$provider) {
            
            // Format
            // -- 
            
            if ($provider['response']['format']['type'] === null) {
                $provider['response']['format']['type'] = $config['response']['format']['type'];
            }
            if ($provider['response']['format']['mime_type'] === null) {
                $provider['response']['format']['mime_type'] = $config['response']['format']['mime_type'];
            }


            // Content Negotiation
            // --

            if ($provider['response']['content_negotiation']['enabled'] === null) {
                $provider['response']['content_negotiation']['enabled'] = $config['response']['content_negotiation']['enabled'];
            }

            if ($provider['response']['content_negotiation']['parameter'] === null) {
                $provider['response']['content_negotiation']['parameter'] = $config['response']['content_negotiation']['parameter'];
            }


            // Pretty Print
            // --

            if ($provider['response']['pretty_print']['enabled'] === null) {
                $provider['response']['pretty_print']['enabled'] = $config['response']['pretty_print']['enabled'];
            }


            // Security 
            // --

            if ($provider['response']['security']['hijacking_prevent']['enabled'] === null) {
                $provider['response']['security']['hijacking_prevent']['enabled'] = $config['response']['security']['hijacking_prevent']['enabled'];
            }
            if ($provider['response']['security']['hijacking_prevent']['x_frame_options'] === null) {
                $provider['response']['security']['hijacking_prevent']['x_frame_options'] = $config['response']['security']['hijacking_prevent']['x_frame_options'];
            }

            if ($provider['response']['security']['checksum']['enabled'] === null) {
                $provider['response']['security']['checksum']['enabled'] = $config['response']['security']['checksum']['enabled'];
            }
            if ($provider['response']['security']['checksum']['algorithm'] === null) {
                $provider['response']['security']['checksum']['algorithm'] = $config['response']['security']['checksum']['algorithm'];
            }


            // Cache Control
            // --

            if ($provider['response']['cache_control']['enabled'] === false) {
                $provider['response']['cache_control']['enabled'] = $config['response']['cache_control']['enabled'];
            }
            if ($provider['response']['cache_control']['public'] === false) {
                $provider['response']['cache_control']['public'] = $config['response']['cache_control']['public'];
            }
            if ($provider['response']['cache_control']['no_store'] === false) {
                $provider['response']['cache_control']['no_store'] = $config['response']['cache_control']['no_store'];
            }
            if ($provider['response']['cache_control']['must_revalidate'] === false) {
                $provider['response']['cache_control']['must_revalidate'] = $config['response']['cache_control']['must_revalidate'];
            }
            if ($provider['response']['cache_control']['max_age'] === -1) {
                $provider['response']['cache_control']['max_age'] = $config['response']['cache_control']['max_age'];
            }


            // CORS
            // --

            if ($provider['response']['cors']['enabled'] === false) {
                $provider['response']['cors']['enabled'] = $config['response']['cors']['enabled'];
            }
            if (empty($provider['response']['cors']['origins'])) {
                $provider['response']['cors']['origins'] = $config['response']['cors']['origins'];
            }
            if (empty($provider['response']['cors']['methods'])) {
                $provider['response']['cors']['methods'] = $config['response']['cors']['methods'];
            }
            if (empty($provider['response']['cors']['expose'])) {
                $provider['response']['cors']['expose'] = $config['response']['cors']['expose'];
            }
            if ($provider['response']['cors']['credentials'] === false) {
                $provider['response']['cors']['credentials'] = $config['response']['cors']['credentials'];
            }
            if ($provider['response']['cors']['max_age'] === -1) {
                $provider['response']['cors']['max_age'] = $config['response']['cors']['max_age'];
            }

        }

        return $config;
    }
}