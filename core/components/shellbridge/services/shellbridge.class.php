<?php
/**
 * A simple bridge service to MODX Shell
 */
class ShellBridge
{
    /**
     * @var modX
     */
    public $modx;
    /**
     * @var MODX\Shell\Application
     */
    protected $shell;
    /**
     * Path to MODX Shell (where 'vendor/autoload.php' is)
     *
     * @var string
     */
    protected $path = '';

    public function __construct(modX $modx, array $options = array())
    {
        $this->modx = $modx;
        $this->path = $this->modx->getOption('shellbridge.shell_path') . 'vendor/autoload.php';
        if (!file_exists($this->path)) {
            throw new Exception('Shell path not valid/configured, check shellbridge.shell_path system setting.');
        }
        $this->getShell();
    }

    /**
     * Run/call the given command
     *
     * @param string $command The command
     * @param array $params Optional arguments & parameters
     *
     * @return array
     * @throws Exception
     */
    public function callCommand($command, array $params = array())
    {
        // Make sure we are in a valid Revo instance directory
        chdir(MODX_BASE_PATH);
        // Allow long running processes
        set_time_limit(0);
        // Try to locate the appropriate command
        $cmd = $this->getShell()->find($command);

        $params['command'] = $command;
        // Prevent interaction
        $params['-n'] = true;

        $input = new Symfony\Component\Console\Input\ArrayInput($params);
        $output = new Symfony\Component\Console\Output\BufferedOutput();

        $status = $cmd->run($input, $output);

        return array(
            'status' => $status,
            'output' => $output->fetch(),
            'params' => $params,
        );
    }

    /**
     * @param string $instanceName
     * @param string $command
     * @param array $params
     *
     * @return array
     */
    private function callCommandOnInstance($instanceName, $command, array $params = array())
    {
        // @todo
        $params['-s'] = $instanceName;

        return $this->callCommand($command, $params);
    }

    /**
     * Convenient method to register a service responsible to load commands
     *
     * @param string $class The service class name
     *
     * @return array
     */
    public function registerService($class)
    {
        return $this->callCommand('extra:component:add', array('service' => $class));
    }

    /**
     * Convenient method to un-register a service responsible to load commands
     *
     * @param string $class The service class name
     *
     * @return array
     */
    public function unregisterService($class)
    {
        return $this->callCommand('extra:component:rm', array('service' => $class));
    }

    /**
     * Grab a MODX Shell instance
     *
     * @return \MODX\Shell\Application
     */
    public function getShell()
    {
        if (!$this->shell instanceof MODX\Shell\Application) {
            // Grab shell path to vendor
            require_once $this->path;

            // Then instantiate the application
            $this->shell = new MODX\Shell\Application();
            //$this->shell->modx = $this->modx;
        }

        return $this->shell;
    }
}
