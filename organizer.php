<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Console\Application;
use PublicVar\Command\SerieOrganizerCommand;
    
$application = new Application();
$application->add(new SerieOrganizerCommand() );
$application->run();
