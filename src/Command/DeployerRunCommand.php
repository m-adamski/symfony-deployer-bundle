<?php

namespace Adamski\Symfony\DeployerBundle\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class DeployerRunCommand extends Command {

    protected static $defaultName = "deployer:run";

    /**
     * @var string
     */
    protected $projectDirectory;

    /**
     * DeployerRunCommand constructor.
     *
     * @param string $projectDirectory
     */
    public function __construct(string $projectDirectory) {
        parent::__construct();

        $this->projectDirectory = $projectDirectory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        $this->setDescription("Configure & Run the deployer tool");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);

        // Choose action
        $choiceOption = $io->choice("Please select the action you want to perform:", [
            "Generate SSH Key",
            "Test the SSH connection",
            "Run the Deployer task"
        ], "Run the Deployer task");

        try {
            switch ($choiceOption) {
                case "Generate SSH Key":
                    $this->generateKey($io);
                    break;
                case "Test the SSH connection":
                    $this->testConnection($io);
                    break;
                case "Run the Deployer task":
                    $this->runDeployer($io);
                    break;
            }
        } catch (Exception $exception) {
            $io->error($exception->getMessage());
        }
    }

    /**
     * @param SymfonyStyle $io
     * @throws Exception
     */
    private function generateKey(SymfonyStyle $io): void {
        $io->section("Generating the SSH key");

        // Define real path to .ssh directory
        $defaultDirectory = $this->generatePath($this->projectDirectory, ".deployer", ".ssh");
        $defaultDirectory = file_exists($defaultDirectory) && is_dir($defaultDirectory) ? $defaultDirectory : null;

        // Ask for path to directory where the keys will be generated
        $generateDirectory = $io->ask("Please enter the path to the folder where the keys will be generated", $defaultDirectory);

        // Check specified path
        if (null !== $generateDirectory && file_exists($generateDirectory) && is_dir($generateDirectory)) {

            // Define the key name and path
            $keyName = $io->ask("Please enter the key file name", "deployer_rsa");
            $keyPath = $this->generatePath($generateDirectory, $keyName);

            // Check if file exist
            if (file_exists($keyPath)) {
                throw new Exception("There is already a key file with the given name");
            }

            // Run process to generate key with ssh-keygen
            // ssh-keygen -t rsa -f %sshKey% -N "" -C ""
            $executeProcess = $this->generateProcess("ssh-keygen", "-t", "rsa", "-f", $keyPath, "-N", "", "-C", "deployer");
            $executeProcess->disableOutput();
            $executeProcess->run(function ($type, $buffer) {
                echo $buffer;
            });
        }
    }

    /**
     * @param SymfonyStyle $io
     * @throws Exception
     */
    private function testConnection(SymfonyStyle $io): void {
        $io->section("Testing the SSH connection");

        // Define real path to configuration file
        $deployerConfigPath = $this->generatePath($this->projectDirectory, ".deployer", "deploy.php");

        // Check if Deployer entry file exist in specified path
        if (!file_exists($deployerConfigPath)) {
            throw new Exception("Configuration file not found in the given location");
        }

        // Ask for hostname to verify
        $verifyHostname = $io->ask("Please enter the hostname to verify");

        // Define path to Deployer vendor
        $deployerVendor = $this->generatePath($this->projectDirectory, "vendor", "bin", "dep");

        // Run process to test SSH connection
        // %rootPath%\vendor\bin\dep --file=.deployer\deploy.php ssh %verifyHostname%
        $executeProcess = $this->generateProcess($deployerVendor, "-f.deployer/deploy.php", "ssh", $verifyHostname);
        $executeProcess->disableOutput();
        $executeProcess->run(function ($type, $buffer) {
            echo $buffer;
        });
    }

    /**
     * @param SymfonyStyle $io
     * @throws Exception
     */
    private function runDeployer(SymfonyStyle $io): void {
        $io->section("Running the Deployer task");

        // Define real path to configuration file
        $deployerConfigPath = $this->generatePath($this->projectDirectory, ".deployer", "deploy.php");

        // Check if Deployer entry file exist in specified path
        if (!file_exists($deployerConfigPath)) {
            throw new Exception("Configuration file not found in the given location");
        }

        // Ask for task name and stage
        $taskName = $io->ask("Please provide the name of the task to run", "deploy");
        $taskStage = $io->ask("Please provide the stage", "develop");

        // Define path to Deployer vendor
        $deployerVendor = $this->generatePath($this->projectDirectory, "vendor", "bin", "dep");

        // Run Deployer task
        // %rootPath%\vendor\bin\dep --file=.deployer\deploy.php %taskName% %taskStage%
        $executeProcess = $this->generateProcess($deployerVendor, "-f.deployer/deploy.php", $taskName, $taskStage);
        $executeProcess->disableOutput();
        $executeProcess->run(function ($type, $buffer) {
            echo $buffer;
        });
    }

    /**
     * Generate process to run.
     *
     * @param string $command
     * @param string ...$args
     * @return Process
     */
    private function generateProcess(string $command, string ...$args): Process {
        return new Process(
            array_merge([$command], $args)
        );
    }

    /**
     * Generate path with specified items.
     *
     * @param string ...$items
     * @return string
     */
    private function generatePath(string ...$items): string {
        return implode(DIRECTORY_SEPARATOR, $items);
    }
}
