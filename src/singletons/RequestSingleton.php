<?php

namespace KaiPfeiffer\WPBase;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @class Auth_Singleton
 * 
 * @version     1.0.0
 * @package     sbu-webapp
 * @author   
 */
class RequestSingleton
{

    use SingletonTrait;

    /*
    *   KONSTANTEN
    */

    /*
    * Regex erlaubte Zeichen für alphanumerische Zeichen
    */
    const REGEX_CLEAN_ALPHANUM      =  '/\W/';

    /*
    * Regex erlaubte Zeichen für Dezimalzahlen
    */
    const REGEX_CLEAN_DECIMAL       =  '/[^\d,\.]/';

    /*
    * Regex erlaubte Zeichen für Datumsangaben
    */
    const REGEX_CLEAN_DATE          =  '/[^\d,\.\-\/]/';

    /*
    * Regex erlaubte Zeichen in E-Mail-Adressen
    */
    const REGEX_CLEAN_EMAIL         =  '/[^A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.\-@]/';

    /*
    * Regex erlaubte Zeichen für Hexadezimalzahlen
    */
    const REGEX_CLEAN_HEXADECIMAL   =  '/[0-9a-fA-F]/';

    /*
    * Regex erlaubte Zeichen für ganze positive Zahlen
    */
    const REGEX_CLEAN_INTEGER       =  '/\D/';

    /*
    * Regex erlaubte Zeichen für Namen, Adrsssen etc.
    */
    const REGEX_CLEAN_STRING        =  '/[^A-Za-z0-9!?#%"\'_\-ÄÜÖßäüö\. ]/';



    /**
     * @var array
     */
    protected $request;


    /**
     * Ein im Authorization-Header übermittelstes Token
     * 
     * @var string
     */
    protected $token;


    /**
     * Der Inhalt eines Tokens
     * 
     * @var object
     */
    protected $token_data;


    /**
     * Eine Instanz der JWT_Singleton-Klasse
     * @var JWT_Singleton
     */
    protected $jwt;

    /**
     * PRIVATE METHODS
     */




    /**
     * PUBLIC METHODS
     */



    /** 
     * function get
     * 
     * liest den angegebenen Parameter aus $_GET, $_POST oder REQUEST_BODY (s.o.) aus.
     * Die Globals werden nacheinander mit absteigender Priorität ausgelesen 
     * ($_GET überschreibt alles)
     *
     * @param      string              der Name des auszulesenden Parameters
     * @param      string  optional    Filter um nur vorgegebene Zeichen auszulesen
     * @param      integer optional    Die Anzahl der Zeichen, die ausgelesen werden soll
     * @result     
     */
    function get($id = null, $clean = null, $truncate = false)
    {
        if (!$id) {
            // Vorsicht die Daten wurden nicht überprüft
            return $this->request;
        }

        $result     = null;
        $regexes    = array(
            'alphanum'      => "sanitize_textfield",
            'date'          => array($this,'dateval'),
            'decimal'       => "floatval",
            'email'         => "sanitize_email",
            'hexadecimal'   => array($this,'hexval'),
            'integer'       => "intval",
            'string'        => "sanitize_text_field",
            'text'          => "sanitize_text_field",
            'tel'           => "sanitize_text_field",
        );

        // nach Parameter suchen 
        if (array_key_exists($id, $this->request)) {
            if (!is_array($this->request[$id])) {
                $result = htmlspecialchars($this->request[$id]);
            } else {
                $result = ($this->request[$id]);
            }
        }

        // wenn Parameter gefunden wurde, diesen bei Bedarf weiterverarbeiten
        if ($result) {

            // wenn Parameter von unerwünschten Zeichen befreit werden soll
            if ($clean) {
                if (isset($regexes[$clean]) && is_callable($regexes[$clean])) {
                    $result = call_user_func($regexes[$clean], $result);
                } else {
                    $result = preg_replace($clean, '', $result);
                }
            }

            // wenn Parameter auf eine bestimmte Länge gekürzt werden soll
            if ($truncate) {
                $result = substr($result, $truncate);
            }
        }

        // Ausgelesenen Parameter zurückgeben, NULL zurückgeben, wenn Parameter nicht existiert
        return $result;
    }

    /** 
     * function getToken
     * 
     * liest den Token aus dem Authorization-Header aus und validiert diesen
     *
     * @param   string      Schlüssel des abzufragenden Wertes
     * @return  array|string|null     
     */
    function getTokenData($key = null)
    {
        $token  = $this->getToken();
        if (!$token) {
            return null;
        }
        if (!$this->jwt) {
            $this->jwt  = JWTSingleton::get_instance();
        }
        $this->token_data   = $this->jwt->decode_jwt($token);

        if (!$this->token_data) {
            return null;
        }

        if(!$key){
            return $this->token_data;
        }

        $value  = isset($this->token_data->{$key}) ? $this->token_data->{$key} : null;
        
        return $value;
    }

    /** 
     * function getToken
     * 
     * liest den Token aus dem Authorization-Header aus und validiert diesen
     *
     * @return string|null     
     */
    function getToken()
    {
        $token      = null;

        if ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null) {
            list($bearer, $token)   = explode(' ', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
            return $token;
        }
        
        if ($_SERVER['HTTP_AUTHORIZATION']) {
            list($bearer, $token)   = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
            return $token;
        }

        return $token;
    }

    public function __toString()
    {
        return json_encode($this->request);
    }

    protected function dateval($value)
    {
        return preg_replace(self::REGEX_CLEAN_DATE, '', $value);
    }

    protected function hexval($value)
    {
        return preg_replace(self::REGEX_CLEAN_HEXADECIMAL, '', $value);
    }

    /**
     * Konstruktor
     */
    protected function __construct()
    {
        $request_body   =  json_decode(file_get_contents("php://input"), true) ?? array();
        $this->request  = array_merge($request_body, $_POST, $_GET);
    }
}
