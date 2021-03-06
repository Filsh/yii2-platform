<?php

namespace yii\platform\runners;

use yii\platform\helpers\FileHelper;
use yii\platform\geo\models\Locations;
use yii\platform\geo\models\LocationBlock;
use yii\platform\geo\models\LocationPoint;
use yii\db\Expression;

class LocationsRunner extends BaseRunner
{
    const CSV_FILE_LOCATION = 'GeoLiteCity-Location.csv';
    const CSV_FILE_BLOCKS = 'GeoLiteCity-Blocks.csv';
    
    public $tmpPath = '@runtime/runner/locations';
    
    public $sourceUrl;
    
    public function run()
    {
        FileHelper::loadFile($this->sourceUrl, [
            'destDir' => $this->tmpPath,
            'onLoad' => [$this, 'resolveFile']
        ]);
        
        FileHelper::removeDirectory($this->tmpPath);
    }
    
    public function resolveFile($file)
    {
        if(!is_file($file)) {
            throw new \yii\base\Exception('Source file not found.');
        }
        
        if(!class_exists('\ZipArchive')) {
            throw new \yii\base\Exception('Not exist ZipArchive class, your must install PECL zip library.');
        }
        
        $z = new \ZipArchive();
        $z->open($file);
        $z->extractTo($this->tmpPath);
        $z->close();
        
        $files = FileHelper::findFiles($this->tmpPath);
        $names = [self::CSV_FILE_LOCATION, self::CSV_FILE_BLOCKS];
        
        foreach($names as $name) {
            foreach($files as $fileCsv) {
                if(!(FileHelper::filterPath($fileCsv, ['only' => [$name], 'basePath' => realpath($fileCsv)]))) {
                    continue;
                }
                $this->resolveCsv($name, $fileCsv);
            }
        }
    }
    
    protected function resolveCsv($name, $file)
    {
        switch($name) {
            case self::CSV_FILE_LOCATION:
                $this->applyLocationCsv($file);
                break;
            case self::CSV_FILE_BLOCKS:
                $this->applyBlockCsv($file);
                break;
        }
    }
    
    protected function applyLocationCsv($file)
    {
        $locationColumns = ['id', 'country', 'region', 'city', 'postal', 'latitude', 'longitude', 'create_time', 'update_time'];
        $licationDuplicates = ['country', 'region', 'city', 'postal', 'latitude', 'longitude'];
        $pointColumns = ['id', 'point'];
        $pointDuplicates = ['point'];
        
        $locationRows = [];
        $pointRows = [];
        if (($handle = fopen($file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if(!isset($data[0]) || intval($data[0]) === 0) {
                    continue;
                }
                
                $locationRows[] = [
                    (int) $data[0],
                    trim($data[1]),
                    trim($data[2]),
                    @iconv(mb_detect_encoding($data[3]), 'UTF-8', $data[3]), // fix error: Incorrect string value: '\xE8s' for column 'city' at row 318
                    trim($data[4]),
                    (float) $data[5],
                    (float) $data[6],
                    time(),
                    time()
                ];
                $pointRows[] = [
                    (int) $data[0],
                    new Expression('GeomFromText("POINT(' . $data[5] . ' ' . $data[6] . ')")')
                ];
                
                if(count($locationRows) === $this->maxExecuteRows) {
                    $this->batchInsertDuplicate(Locations::tableName(), $locationColumns, $locationRows, $licationDuplicates)->execute();
                    $this->batchInsertDuplicate(LocationPoint::tableName(), $pointColumns, $pointRows, $pointDuplicates)->execute();
                    
                    $locationRows = [];
                    $pointRows = [];
                }
            }
            
            if(count($locationRows) > 0) {
                $this->batchInsertDuplicate(Locations::tableName(), $locationColumns, $locationRows, $licationDuplicates)->execute();
                $this->batchInsertDuplicate(LocationPoint::tableName(), $pointColumns, $pointRows, $pointDuplicates)->execute();
            }
            
            fclose($handle);
        }
    }
    
    public function applyBlockCsv($file)
    {
        $columns = ['id', 'start', 'end'];
        $update = ['id', 'start'];
        
        $rows = [];
        if (($handle = fopen($file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if(!isset($data[2]) || intval($data[2]) === 0) {
                    continue;
                }
                
                $rows[] = [
                    (int) $data[2],
                    (int) $data[0],
                    (int) $data[1]
                ];
                
                if(count($rows) === $this->maxExecuteRows) {
                    $this->batchInsertDuplicate(LocationBlock::tableName(), $columns, $rows, $update)->execute();
                    $rows = [];
                }
            }
            
            if(count($rows) > 0) {
                $this->batchInsertDuplicate(LocationBlock::tableName(), $columns, $rows, $update)->execute();
            }
            
            fclose($handle);
        }
    }
}