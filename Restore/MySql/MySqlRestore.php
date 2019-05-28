<?php
namespace ENC\Bundle\BackupRestoreBundle\Restore\MySql;

use ENC\Bundle\BackupRestoreBundle\Restore\AbstractRestore;
use ENC\Bundle\BackupRestoreBundle\Exception\RestoreException;

class MySqlRestore extends AbstractRestore
{
    public function restoreDatabase($file)
    {
        if (!is_string($file)) {
            throw new \InvalidArgumentException('First argument must be a string with the full path to the SQL file.');
        }

        if (!is_file($file)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist.', $file));
        }
        
        $this->callVendorRestoreTool($file);
    }
    
    public function callVendorRestoreTool($file)
    {
        if (!$this->doCallVendorRestoreTool($file)) {
            $exception = new RestoreException('An error occurred while working on the restore of your database. For more details, please look at the output of the command using the "getOutput" method of the exception.');
            $exception->setOutput($this->getLastCommandOutput());
            
            throw $exception;
        }
    }
    
    protected function doCallVendorRestoreTool($file)
    {
        $connection = $this->getConnection();
        $returnValue = '';
        $output = array();
        $commandToExecute = sprintf('mysql --host="%s" --port="%s" --user="%s" --password="%s" %s < %s;', 
            $connection->getHost(), 
            $connection->getPort(), 
            $connection->getUsername(), 
            $connection->getPassword(), 
            $connection->getDatabase(),
            $file);
            
        $returnLine = exec($commandToExecute, $output, $returnValue);
        
        $this->setLastCommandOutput($output);
        
        if ($returnValue !== 0) {
            $this->log($returnLine, $output, $returnValue);

            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $returnLine
     * @param array $output
     * @param string $returnValue
     */
    protected function log($returnLine, $output, $returnValue)
    {
        file_put_contents(
            '/tmp/jenkins_test.log',
            sprintf(
                "[%s] returnLine: %s;\n".
                "returnValue: %s\n".
                "output: %s\n",
                date('Y-m-d H:i:s'),
                $returnLine,
                $returnValue,
                var_export($output, 1)
            ),
            8
        );
    }
}