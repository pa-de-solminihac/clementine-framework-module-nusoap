<?php
class nusoapNusoapModel extends nusoapNusoapModel_Parent
{
    /**
     * __construct : le constructeur se charge des include et s'enregistre dans le registre Clementine pour les futurs appels
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        if (!isset(Clementine::$register['nusoap'])) {
            // l'extension PHP soap ne fonctionne pas bien, j'utilise donc nusoap
            require(__FILES_ROOT_NUSOAP__ . '/lib/nusoap-0.9.5/nusoap.php');
            // connexion, avec ou sans le cache selon la valeur de config[module_nusoap][cache]
            if (Clementine::$config['module_nusoap']['cache']) {
                require(__FILES_ROOT_NUSOAP__ . '/lib/nusoap-0.9.5/class.wsdlcache.php');
            }
            Clementine::$register['nusoap'] = array();
            Clementine::$register['nusoap']['client'] = array();
        }
        return Clementine::$register['nusoap'];
    }

    /**
     * getClient : gère la connexion au webservice, gère le cache, et renvoie un objet nusoap_client (enregistré dans le registre Clémentine pour les futurs appels)
     * 
     * @param mixed $uri 
     * @param string $options 
     * @access public
     * @return void
     */
    public function getClient($uri, $options = array('trace' => '0'))
    {
        if (!isset(Clementine::$register['nusoap']['client'][$uri])) {
            if (Clementine::$config['module_nusoap']['cache']) {
                // duree du cache par defaut : 10min
                $cache_lifetime = 0;
                if (isset(Clementine::$config['module_nusoap']['cache_lifetime'])) {
                    $cache_lifetime = Clementine::$config['module_nusoap']['cache_lifetime'];
                }
                if (!$cache_lifetime) {
                    $cache_lifetime = 600;
                }
                // repertoire du cache par defaut : soap.wsdl_cache_dir, ou /tmp sinon
                if (isset(Clementine::$config['module_nusoap']['cache_dir']) && Clementine::$config['module_nusoap']['cache_dir']) {
                        $wsdl_cache_dir = Clementine::$config['module_nusoap']['cache_dir'];
                } else {
                    $wsdl_cache_dir = ini_get("soap.wsdl_cache_dir");
                    if (!$wsdl_cache_dir) {
                        $wsdl_cache_dir = '/tmp';
                    }
                }
                // connexion avec activation du cache WSDL
                $cache = new nusoap_wsdlcache($wsdl_cache_dir, $cache_lifetime);
                $wsdl = $cache->get($uri);
                if (!$wsdl) {
                    $wsdl = new wsdl($uri, '', '', '', '', 30); // connection timeout 30s
                    $cache->put($wsdl);
                }
                Clementine::$register['nusoap']['client'][$uri] = new nusoap_client($wsdl, $options);
            } else {
                Clementine::$register['nusoap']['client'][$uri] = new nusoap_client($uri, $options);
            }
            $err = Clementine::$register['nusoap']['client'][$uri]->getError();
            if ($err) {
                if (__DEBUGABLE__) {
                    trigger_error("SOAP Fault: $err", E_USER_ERROR);
                } else {
                    die();
                }
            }
        }
        return Clementine::$register['nusoap']['client'][$uri];
    }

}
?>
