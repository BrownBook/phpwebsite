<?php

namespace Canopy;

/**
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @package phpws2
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */

/**
 * This class handles requests made by the previous page. The request will be
 * either a GET (requesting a view or response), a POST (submission of information
 * leading to change in the system), or a PUT (creation of a new item n the system).
 */
class Request extends Data
{

    /**
     * Constant defining a GET request was sent.
     */
    const GET = 'GET';

    /**
     * Constant defining a HEAD request was sent.
     */
    const HEAD = 'HEAD';

    /**
     * Constant defining a POST request was sent.
     */
    const POST = 'POST';

    /**
     * Constant defining a PUT request was sent.
     */
    const PUT = 'PUT';

    /**
     * Constant defining a DELETE request was sent.
     */
    const DELETE = 'DELETE';

    /**
     * Constant defining a OPTIONS request was sent.
     */
    const OPTIONS = 'OPTIONS';

    /**
     * Constant defining a PATCH request was sent.
     */
    const PATCH = 'PATCH';

    /**
     * An array of commands derived from the url
     * @var array
     */
    protected $commands;

    /**
     * Holds the key/value data available from the various request methods
     * @var vars
     */
    protected $vars = null;

    /**
     * Holds the raw Data field from the Request.  This could be JSON data
     * (application/json) or it could be raw form data
     * (application/x-www-form-urlencoded or multipart/form-data) - it is up to
     * the programmer to decide.
     */
    private $data = null;

    /**
     * The currently requested module. This will be contained in the
     * POST/GET/PUT.
     *
     * @var string
     */
    private $module = null;

    /**
     * A copy of the current url.
     * @var string
     */
    private $url = null;

    /**
     * The state of the current command
     * GET is the default state
     * @var boolean
     */
    private $method = null;

    /**
     * An instance of Http\Accept, which should be used to determine the type of
     * data that will be sent to the client.
     * @var Http\Accept
     */
    private $accept;

    /**
     * The last command shifted off the command stack.
     * @var string
     */
    private $last_command;

    /**
     * Holds array of GET values only
     * @var array
     */
    private $getVars;

    /**
     * Holds array of POST values only
     * @var array
     */
    private $postVars;

    /**
     * Holds array of PATCH values only
     * @var array
     */
    private $patchVars;

    /**
     * Holds array of DELETE values only
     * @var array
     */
    private $deleteVars;

    /**
     * Holds array of PUT values only
     * @var array
     */
    private $putVars;

    /**
     * Builds the current page request object.
     *
     * @param $url string The URL
     * @param $vars array|null Request Variables ($_REQUEST, etc)
     * @param $data mixed The raw content area of the HTTP request (JSON and
     *                    Form data)
     * @param $accept Http\Accept
     */
    public function __construct($url, $method, array $vars = null, $data = null,
            \phpws2\Http\Accept $accept = null)
    {
        $this->getVars = array();
        $this->postVars = array();

        $this->setUrl($url);
        $this->setMethod($method);

        if (is_null($vars)) {
            $vars = array();
        }
        $this->setVars($vars);

        $this->setData($data);

        // @todo I am a bit worried about the default here; in fact, it should
        // probably not be allowed to be null at all.
        if (is_null($accept))
            $accept = new \phpws2\Http\Accept('text/html');
        $this->setAccept($accept);
        $this->buildCommands();
    }

    /**
     * Receives the page url, parses it, and sets the module and commands based
     * on what it finds.
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        if (preg_match('/index\.php$/', $url)) {
            $this->url = '/';
            return;
        }

        // Ensure consistency in URLs
        $this->url = $this->sanitizeUrl($url);
    }

    /**
     * Builds the commands parameter based on the current url.
     */
    public function buildCommands()
    {
        $url = preg_replace('@^/@', '', $this->url);
        $cmds = explode('/', $url);
        if ($cmds[0] == $this->module) {
            array_shift($cmds);
        }
        $this->commands = $cmds;
    }

    /**
     * Shifts a command from the front of the stack
     * @return type
     */
    public function shiftCommand()
    {
        $this->last_command = array_shift($this->commands);
        return $this->last_command;
    }

    /**
     * Turns all of the various and wonderful things you can do with a URL into
     * a consistent query, for example /a/./b/ becomes /a/b/.
     * @param string $url The URL to sanitize
     * @return string The sanitized URL
     */
    public function sanitizeUrl($url)
    {
        // Repeated Slashes become One Slash
        $url = preg_replace('@//+@', '/', $url);

        // Fix all instances of dot as "current directory"
        $url = preg_replace('@^(\./)+@', '', $url);
        $url = preg_replace('@(/\.)+$@', '/', $url);
        $url = preg_replace('@/(\./)+@', '/', $url);

        // Ensure Preceding Slash
        if (substr($url, 0, 1) != '/') {
            $url = '/' . $url;
        }

        // Remove Trailing Slash
        if (substr($url, -1, 1) == '/' && strlen($url) > 1) {
            $url = substr($url, 0, -1);
        }

        // Strip parameters
        $url = preg_replace('/(\?|&)\w.*$/', '', $url);
        return $url;
    }

    /**
     * @return string The currently set url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the Data component, which should hold JSON and Form Post data
     *
     * @param $data mixed The Data
     */
    protected function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Returns the raw POST data from the request.
     *
     * @return string The raw POST data.
     */
    public function getRawData()
    {
        return $this->data;
    }

    /**
     * Tries to json_decode the raw POST data from the request.  Please note
     * that the programmer must decide if this is what they were expecting.
     *
     * Same as a call to json_decode($request->getRawData());
     *
     * @return array The json_decoded POST data.
     */
    public function getJsonData()
    {
        return json_decode($this->getRawData());
    }

    /**
     * @return boolean True if the request is from a POST
     */
    public function isPost()
    {
        return $this->method == self::POST;
    }

    /**
     * @return boolean True if the request is from a GET
     */
    public function isGet()
    {
        return $this->method == self::GET;
    }

    /**
     * @return boolean True is the request is from a PUT
     */
    public function isPut()
    {
        return $this->method == self::PUT;
    }

    public function isPatch()
    {
        return $this->method == self::PATCH;
    }

    public function isDelete()
    {
        return $this->method == self::DELETE;
    }

    public function setVars(array $vars)
    {
        $this->vars = $vars;

        // 1.x Compatibility
        if (array_key_exists('module', $vars)) {
            $this->setModule($vars['module']);
        }
    }

    /**
     * Checks to see if the $variable_name exists in the $vars parameter. If so,
     * it is returned, else the $then parameter is returned.
     * @param string $variable_name
     * @param mixed $then
     * @return mixed
     */
    public function ifNotVarThen($variable_name, $then)
    {
        return $this->isVar($variable_name) ? $this->getVar($variable_name) : $then;
    }

    /**
     * @param string $variable_name
     * @return boolean True if the variable is on the REQUEST
     */
    public function isVar($variable_name)
    {
        return array_key_exists($variable_name, $this->vars);
    }

    /**
     * Returns true is variable is not set or is empty (0, '', null)
     * @param string $variable_name
     * @return boolean true
     */
    public function isEmpty($variable_name)
    {
        return array_key_exists($variable_name, $this->vars) && empty($this->vars[$variable_name]);
    }

    /**
     * @param $variable_name string The name of the request variable to get
     * @param $default string|null The default value to return if not set
     * @return string The value of the requested variable
     */
    public function getVar($variable_name, $default = null)
    {
        if (!$this->isVar($variable_name)) {
            if (isset($default)) {
                return $default;
            } else {
                throw new \Exception(sprintf('Variable "%s" not found',
                        $variable_name));
            }
        }

        return $this->vars[$variable_name];
    }

    /**
     * Returns all the variables set in the vars variable.
     * @return array
     */
    public function getRequestVars()
    {
        return $this->vars;
    }

    /**
     * @param $variable_name string The name of the request variable to set
     * @param $value string The value for the request variable
     * @return void
     */
    public function setVar($variable_name, $value)
    {
        $this->vars[$variable_name] = $value;
    }

    /**
     * Manually sets the state of the request.
     * @param integer $state
     * @return void | Exception if unknown type
     */
    public function setMethod($method)
    {
        if (in_array($method,
                        array(self::PUT, self::POST, self::GET, self::DELETE, self::OPTIONS, self::PATCH, self::HEAD))) {
            $this->method = $method;
        } else {
            throw new \Exception('Unknown state type');
        }
    }

    /**
     * Returns the current request method in plain text
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * The current module needed to be acted upon
     * @param string $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     *
     * @return string The current module needed to be acted on
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Sets the Accept object
     * @param $accept Http\Accept The Accept object for this request
     */
    public function setAccept(\phpws2\Http\Accept $accept)
    {
        $this->accept = $accept;
    }

    /**
     * Gets the Accept object
     * @return Http\Accept The Accept object for this request
     */
    public function getAccept()
    {
        return $this->accept;
    }

    public function getCurrentToken()
    {
        preg_match('@^(/[^/]*)@', $this->getUrl(), $matches);

        if ($matches[0] == '/')
            return '/';

        return substr($matches[0], 1);
    }

    /**
     * Pops the last token off the Request and returns a new Request object. So
     * <code>
     * # using a url like "moduleName/Alpha/Beta"
     *
     * echo $request->getCurrentToken();
     * // prints "Alpha"
     *
     * $new_r = $request->getNextRequest();
     * echo $new_r->getCurrentToken();
     * // prints "Beta"
     * </code>
     * @deprecated Use shiftCommand
     * @return \Canopy\Request
     */
    public function getNextRequest()
    {
        $url = preg_replace('@^/[^/]*@', '', $this->getUrl());

        $request = new Request(
                $url, $this->getMethod(), $this->getRequestVars(),
                $this->getRawData(), $this->getAccept());
        $request->setPostVars($this->postVars);
        $request->setGetVars($this->getVars);
        return $request;
    }

    /**
     * Checks to see the current require was an ajax request by reading the jquery
     * header. JQUERY required for this.
     * @return boolean
     */
    public static function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    }

    public static function show()
    {
        if (!empty($_POST)) {
            ob_start();
            var_dump($_POST);
            $post_vars = ob_get_clean();
        } else {
            $post_vars = 'Empty';
        }

        if (!empty($_GET)) {
            ob_start();
            var_dump($_GET);
            $get_vars = ob_get_clean();
        } else {
            $get_vars = 'Empty';
        }

        $content[] = '<h2>$_POST</h2>' . $post_vars;
        $content[] = '<hr>';
        $content[] = '<h2>$_GET</h2>' . $get_vars;

        echo implode('', $content);
    }

    /**
     * Returns true is the file_name is in the _FILES array
     * @param string $file_name
     * @return boolean
     */
    public function isUploadedFile($file_name)
    {
        return isset($_FILES[$file_name]) && $_FILES[$file_name]['size'];
    }

    /**
     * Returns the _FILES array for the request file name.
     * @param string $file_name
     * @return array
     * @throws \Exception
     */
    public function getUploadedFileArray($file_name)
    {
        if (!$this->isUploadedFile($file_name)) {
            throw new \Exception(sprintf('File "%s" was not uploaded',
                    $file_name));
        }

        return $_FILES[$file_name];
    }

    /**
     * Returns the last_command variable
     * @return string
     */
    public function lastCommand()
    {
        return $this->last_command;
    }

    /**
     * @param array $post
     */
    public function setPostVars($vars)
    {
        $this->postVars = $vars;
    }

    /**
     * @param array $vars
     */
    public function setPatchVars($vars)
    {
        $this->patchVars = $vars;
    }

    /**
     * @param array $vars
     */
    public function setPutVars($vars)
    {
        $this->putVars = $vars;
    }

    /**
     * @param array $vars
     */
    public function setDeleteVars($vars)
    {
        $this->deleteVars = $vars;
    }

    public function setGetVars($vars)
    {
        $this->getVars = $vars;
    }

    public function pullPostVar($name)
    {
        if (!isset($this->postVars[$name])) {
            throw new \phpws2\Exception\ValueNotSet($name);
        }
        return $this->postVars[$name];
    }

    public function pullPatchVar($name)
    {
        if (!isset($this->patchVars[$name])) {
            throw new \phpws2\Exception\ValueNotSet($name);
        }
        return $this->patchVars[$name];
    }

    public function pullPutVar($name)
    {
        if (!isset($this->putVars[$name])) {
            throw new \phpws2\Exception\ValueNotSet($name);
        }
        return $this->putVars[$name];
    }

    public function pullDeleteVar($name)
    {
        if (!isset($this->deleteVars[$name])) {
            throw new \phpws2\Exception\ValueNotSet($name);
        }
        return $this->deleteVars[$name];
    }

    public function pullGetVar($name)
    {
        if (!isset($this->getVars[$name])) {
            throw new \phpws2\Exception\ValueNotSet($name);
        }
        return $this->getVars[$name];
    }

    public function postVarIsset($name)
    {
        return isset($this->postVars[$name]);
    }

    public function patchVarIsset($name)
    {
        return isset($this->patchVars[$name]);
    }

    public function putVarIsset($name)
    {
        return isset($this->putVars[$name]);
    }

    public function deleteVarIsset($name)
    {
        return isset($this->deleteVars[$name]);
    }

    public function getVarIsset($name)
    {
        return isset($this->getVars[$name]);
    }

    public function pullPostVarIfSet($name)
    {
        return $this->postVarIsset($name) ? $this->pullPostVar($name) : false;
    }

    public function pullPatchVarIfSet($name)
    {
        return $this->patchVarIsset($name) ? $this->pullPatchVar($name) : false;
    }

    public function pullPutVarIfSet($name)
    {
        return $this->putVarIsset($name) ? $this->pullPutVar($name) : false;
    }

    public function pullDeleteVarIfSet($name)
    {
        return $this->deleteVarIsset($name) ? $this->pullDeleteVar($name) : false;
    }

    public function pullGetVarIfSet($name)
    {
        return $this->getVarIsset($name) ? $this->pullGetVar($name) : false;
    }

    public function pullPostString($varname, $test_isset = false)
    {
        if ($test_isset && !$this->postVarIsset($varname)) {
            return false;
        }

        return $this->filterString($this->pullPostVar($varname));
    }

    public function pullPutString($varname, $test_isset = false)
    {
        if ($test_isset && !$this->putVarIsset($varname)) {
            return false;
        }

        return $this->filterString($this->pullPutVar($varname));
    }

    public function pullPatchString($varname, $test_isset = false)
    {
        if ($test_isset && !$this->patchVarIsset($varname)) {
            return false;
        }

        return $this->filterString($this->pullPatchVar($varname));
    }

    public function pullDeleteString($varname, $test_isset = false)
    {
        if ($test_isset && !$this->deleteVarIsset($varname)) {
            return false;
        }

        return $this->filterString($this->pullDeleteVar($varname));
    }

    public function pullGetString($varname, $test_isset = false)
    {
        if ($test_isset && !$this->getVarIsset($varname)) {
            return false;
        }

        return $this->filterString($this->pullGetVar($varname));
    }

    public function pullPostBoolean($varname, $test_isset = false)
    {
        if ($test_isset && !$this->postVarIsset($varname)) {
            return null;
        }
        return $this->filterBoolean($this->pullPostVar($varname));
    }

    public function pullPutBoolean($varname, $test_isset = false)
    {
        if ($test_isset && !$this->putVarIsset($varname)) {
            return null;
        }
        return $this->filterBoolean($this->pullPutVar($varname));
    }

    public function pullPatchBoolean($varname, $test_isset = false)
    {
        if ($test_isset && !$this->patchVarIsset($varname)) {
            return null;
        }
        return $this->filterBoolean($this->pullPatchVar($varname));
    }

    public function pullDeleteBoolean($varname, $test_isset = false)
    {
        if ($test_isset && !$this->deleteVarIsset($varname)) {
            return null;
        }
        return $this->filterBoolean($this->pullDeleteVar($varname));
    }

    public function pullGetBoolean($varname, $test_isset = false)
    {
        if ($test_isset && !$this->getVarIsset($varname)) {
            return null;
        }
        return $this->filterBoolean($this->pullGetVar($varname));
    }

    public function pullPostArray($varname, $test_isset = false)
    {
        if ($test_isset && !$this->postVarIsset($varname)) {
            return null;
        }
        
        return $this->pullPostVar($varname);
    }
    
    public function pullPutArray($varname, $test_isset = false)
    {
        if ($test_isset && !$this->putVarIsset($varname)) {
            return null;
        }
        
        return $this->pullPutVar($varname);
    }

    public function pullDeleteArray($varname, $test_isset = false)
    {
        if ($test_isset && !$this->deleteVarIsset($varname)) {
            return null;
        }
        
        return $this->pullDeleteVar($varname);
    }
    
    public function pullPatchArray($varname, $test_isset = false)
    {
        if ($test_isset && !$this->patchVarIsset($varname)) {
            return null;
        }
        
        return $this->pullPatchVar($varname);
    }

    public function pullGetArray($varname, $test_isset = false)
    {
        if ($test_isset && !$this->getVarIsset($varname)) {
            return null;
        }
        
        return $this->pullGetVar($varname);
    }
    

    public function pullPostInteger($varname, $test_isset = false)
    {
        if ($test_isset && !$this->postVarIsset($varname)) {
            return false;
        }
        return $this->filterInteger($this->pullPostVar($varname));
    }

    public function pullPutInteger($varname, $test_isset = false)
    {
        if ($test_isset && !$this->putVarIsset($varname)) {
            return false;
        }
        return $this->filterInteger($this->pullPutVar($varname));
    }

    public function pullPatchInteger($varname, $test_isset = false)
    {
        if ($test_isset && !$this->patchVarIsset($varname)) {
            return false;
        }
        return $this->filterInteger($this->pullPatchVar($varname));
    }

    public function pullDeleteInteger($varname, $test_isset = false)
    {
        if ($test_isset && !$this->deleteVarIsset($varname)) {
            return false;
        }
        return $this->filterInteger($this->pullDeleteVar($varname));
    }

    public function pullGetInteger($varname, $test_isset = false)
    {
        if ($test_isset && !$this->getVarIsset($varname)) {
            return false;
        }
        return $this->filterInteger($this->pullGetVar($varname));
    }

    public function pullPostFloat($varname, $test_isset = false)
    {
        if ($test_isset && !$this->postVarIsset($varname)) {
            return false;
        }
        return $this->filterFloat($this->pullPostVar($varname));
    }

    public function pullPatchFloat($varname, $test_isset = false)
    {
        if ($test_isset && !$this->patchVarIsset($varname)) {
            return false;
        }
        return $this->filterFloat($this->pullPatchVar($varname));
    }

    public function pullPutFloat($varname, $test_isset = false)
    {
        if ($test_isset && !$this->putVarIsset($varname)) {
            return false;
        }
        return $this->filterFloat($this->pullPutVar($varname));
    }

    public function pullDeleteFloat($varname, $test_isset = false)
    {
        if ($test_isset && !$this->deleteVarIsset($varname)) {
            return false;
        }
        return $this->filterFloat($this->pullDeleteVar($varname));
    }

    public function pullGetFloat($varname, $test_isset = false)
    {
        if ($test_isset && !$this->getVarIsset($varname)) {
            return false;
        }
        return $this->filterFloat($this->pullGetVar($varname));
    }

    /**
     * Returns a SANITIZED filtered string.
     * @param mixed $value
     * @return string
     */
    public function filterString($value)
    {
        return trim(strip_tags(htmlspecialchars($value)));
    }

    /**
     * Tests value to see if value is boolean. Null is returned
     * if it is not, true/false otherwise.
     * @param mixed $value
     * @return mixed true/false or null if not boolean
     */
    public function filterBoolean($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE);
    }

    /**
     * Returns integer if is such, false if not an integer
     * @param mixed $value
     * @return integer
     */
    public function filterInteger($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * Returns float if is such, false if not an float.
     * Be aware the filter will say 1 is a float. The actual
     * is_float php function will return false on 1. 
     * @param mixed $value
     * @return float
     */
    public function filterFloat($value)
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    public function pullPostVars()
    {
        return $this->postVars;
    }

    public function pullGetVars()
    {
        return $this->getVars;
    }

    public function pullPatchVars()
    {
        return $this->patchVars;
    }

    public function pullPutVars()
    {
        return $this->putVars;
    }

    public function pullDeleteVars()
    {
        return $this->deleteVars;
    }

    public function listVars()
    {
        $varlist['GET'] = $this->getVars;
        switch ($this->method) {
            case self::DELETE:
                $varlist['DELETE'] = $this->deleteVars;
                break;

            case self::PATCH:
                $varlist['PATCH'] = $this->patchVars;
                break;

            case self::POST:
                $varlist['POST'] = $this->postVars;
                break;

            case self::PUT:
                $varlist['PUT'] = $this->putVars;
                break;
        }
        return $varlist;
    }

}
