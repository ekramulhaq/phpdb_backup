<?php 

class Backup {
    public  $db;
    public  $file_name;
    private $in_calling;
    
    public function __construct(){
        $this->db = require("db.php");
    }
    
    function backup_tables($tables = '*',$drop=1,$create_table=1)
    {
	
        if($tables == '*')
        {
           $tables = $this->db->list_tables();
        }
        else
        {
            $tables = is_array($tables) ? $tables : explode(',',$tables);
        }
        
        $sql = '';
        foreach($tables as $table)
        {
            $result = $this->db->query('SELECT * FROM '.$table);
            $num_fields = $result->num_fields();
            $actual_result =  $result->result_id;
            
            if($drop)
                $sql.= 'DROP TABLE IF EXISTS '.$table.';';
            
            if($create_table){
                $create_tbl = "SHOW CREATE TABLE $table";
                $table_info = $this->db->query($create_tbl)
                                    ->result_id
                                    ->fetch_row()[1];  
                $sql.= "\n\n".$table_info.";\n\n";
            }
            
            while($row = $actual_result->fetch_row())
            {
                $sql.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j < $num_fields; $j++) 
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $sql.= '"'.$row[$j].'"' ; } else { $sql.= '""'; }
                    if ($j < ($num_fields-1)) { $sql.= ','; }
                }
                $sql.= ");\n";
            }
            
            $sql.="\n\n\n";
        }
        
        //save file
        $this->file_name = 'db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql';
        $handle = fopen($this->file_name,'w+');
        fwrite($handle,$sql);
        fclose($handle);
            
    }
    
    public  function download($tables='*'){
        $this->in_calling = true;
        $this->backup_tables($tables);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$this->file_name.'"');
        readfile($this->file_name);
        
    }
    
}

return new Backup();


