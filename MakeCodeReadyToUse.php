<?php
/**
 * Copyright © 2018 Alexandre GUASCH for Magento
 */
namespace Magento\Migration\Internal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCodeReadyToUse extends Command
{
    /**
     * @var \Magento\Migration\Logger\Logger
     */
    protected $logger;

    /**
     * @param \Magento\Migration\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Migration\Logger\Logger $logger
    ) {
        parent::__construct();
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('makeCodeReadyToUse')
            ->setDescription('Remove all php and rename all converted files')
            ->addArgument(
                'inputPath',
                InputArgument::REQUIRED,
                'Base directory of dst'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dstBaseDir = $input->getArgument('inputPath');

        if (!is_dir($dstBaseDir)) {
            $this->logger->error('dst path doesn\'t exist or not a directory');
            return 255;
        }
		
		if ( substr($dstBaseDir, strlen($dstBaseDir)-1) != '/' ){
			$dstBaseDir .= '/';
		}
		
		if ( !$this->crawl($dstBaseDir) ){
			return 255;
		}
		
		$this->logger->info('Code is now ready to use !');

        return 0;
    }
	
	/**
     * @param InputInterface $dir
     * @return boolean
     */
	private function crawl($dir)
	{
		$files = array_diff(scandir($dir), ['.','..']);
		
		foreach ( $files as $file ){
			if ( is_dir($dir.$file) ){
				if ( $this->crawl($dir.$file.'/') == false ){
					return false;
				}
				continue;
			}
			
			$ext = substr(strrchr($file, '.'), 1);
			
			if ( $ext == 'php' ){
				if ( !unlink($dir.$file)){
					$this->logger->error('Could not remove ' . $dir.$file . ' : check writing permissions');
					return false;
				}
				$this->logger->info($dir.$file . ' was removed');
			}
			else if( $ext == 'converted' ){
				$newFileName = str_replace(".converted", "", $file);
				if ( !rename($dir.$file, $dir.$newFileName) ){
					$this->logger->error('Could not rename ' . $dir.$file . ' : check writing permissions');
					return false;
				}
				$this->logger->info($dir.$file . ' was renamed to ' . $dir.$newFileName);
			}
		}
		
		return true;
	}
}
