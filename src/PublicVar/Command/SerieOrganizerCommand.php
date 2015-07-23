<?php

namespace PublicVar\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class SerieOrganizerCommand extends Command
{

    /**
     * @var OutputInterface 
     */
    private $output;

    /**
     * @var bool if true hide output, show otherwise
     */
    private $hideOutput;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        /**
         * Using example : php organizer.php series /home/me/Videos --minimize-name
         */
        $this
            ->setName('series')
            ->setDescription('Organize our series files ')
            ->addArgument(
                'target-directory', InputArgument::OPTIONAL, 'Do you want to move all the series directories in to a target directory ?'
            )
            ->addOption(
                'hide-output', null, InputOption::VALUE_NONE, 'Hide the output in the console'
            )
        ;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->hideOutput = $input->getOption('hide-output') ? true : false;
        $targetDirectory = $input->getArgument('target-directory');

        if (empty($targetDirectory)) {
            $targetDirectory = getcwd();
        }

        if (is_dir($targetDirectory)) {
            $seriesFiles = $this->listFiles($targetDirectory);

            foreach ($seriesFiles as $file) {

                $serieName = $this->getSerieName($file->getFileName());
                $destination = $targetDirectory . '/' . $serieName . '/' . $file->getFileName();
                $this->createDirectory($targetDirectory . '/' . $serieName);
                $this->moveFile($file, $destination);
            }
        }
        else {
            $output->writeln('<error>This directory is not valid :' . $targetDirectory . ' </error>');
        }
    }

    /**
     * List all the files (video serie files) in the given directory
     * 
     * @param string $directory 
     * @param string|regex $name name of the files we are looking for
     * @return array of SplFileInfo list of videos files
     */
    private function listFiles($directory, $name = '/\.(mkv|avi|mpg|mp4)$/', $depth = '== 0')
    {
        $finder = new Finder();
        $finder->files()->name($name)
            ->in($directory)
            ->depth($depth)
        ;
        return iterator_to_array($finder);
    }

    /**
     * Extract the serie's name from the file name. It's based on "My Serie s01e02.avi"
     * 
     * @param string $fileName
     * @return string
     */
    private function getSerieName($fileName)
    {
        $cleanName = preg_replace('/[^a-z0-9]+/i', ' ', $fileName);
        $cleanName = preg_replace("/s[0-9]{2}\.* *e[0-9]{2}.+(mkv|avi|mp4|mpg)$/i", " ", $cleanName);
        $cleanName = trim($cleanName);
        $cleanName = ucwords($cleanName);

        return $cleanName;
    }

    /**
     * Create the serie directory
     * 
     * @param string $directory
     */
    private function createDirectory($directory)
    {
        if (!empty($directory) && !is_dir($directory)) {
            //create a directory (unix system)
            $execCreateDirectory = 'mkdir -p "' . $directory . '"';
            $process = new Process($execCreateDirectory, null, null, null, null);
            $process->run(function ($type, $buffer) {
                if ('err' === $type) {
                    $this->display('create directory Error > ' . $buffer);
                }
                else {
                    $this->display('directory created > ' . $buffer);
                }
            });
        }
    }

    /**
     * Move the the files.
     * 
     * @param string $origine origine path file
     * @param string $destination destination path file
     */
    private function moveFile($origine, $destination)
    {
        $this->display('move file > ' . $origine, 'comment');
        //transform space in "\ " for the mv command for unix system
        $origine = str_replace(' ', '\ ', $origine);
        $destination = str_replace(' ', '\ ', $destination);

        $execMoveFile = "mv -f $origine $destination";
        $process = new Process($execMoveFile, null, null, null, null);
        $process->run(function ($type, $buffer) {
            //we dispaly to user which files is moved or the error
            $this->display('move file');
            if ('err' === $type) {
                $this->display('move file error > ' . $buffer);
            }
            else {
                $this->display('file moved > ' . $buffer);
            }
        });
    }

    /**
     * Display the message in the console
     * 
     * @param string $message the message to display
     * @param string $messageType the message type. available : info, comment, question, error
     */
    private function display($message, $messageType = '')
    {
        switch ($messageType) {
            case 'info':
                $message = '<info>' . $message . '</info>';
                break;
            case 'comment':
                $message = '<comment>' . $message . '</comment>';
                break;
            case 'question':
                $message = '<question>' . $message . '</question>';
                break;
            case 'error':
                $message = '<error>' . $message . '</error>';
                break;
        }
        if (!$this->hideOutput) {
            $this->output->writeln($message);
        }
    }

}
