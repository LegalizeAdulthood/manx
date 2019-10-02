<?php

class DatabaseTester
{

    public static function createResultRowsForColumns($columns, $data)
    {
        $rows = array();
        foreach ($data as $item)
        {
            $row = array();
            for ($i = 0; $i < count($columns); $i++)
            {
                $row[$columns[$i]] = $item[$i];
            }
            array_push($rows, $row);
        }
        return $rows;
    }
}
