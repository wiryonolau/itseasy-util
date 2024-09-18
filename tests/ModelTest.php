<?php

namespace Itseasy\Test;

use PHPUnit\Framework\TestCase;
use DateTimeInterface;
use Itseasy\Stdlib\ArrayUtils;
use Itseasy\Model\ParameterSetModel;

final class ModelTest extends TestCase
{
    public function testBaseModel()
    {
        $base = new Model\TestModel();
        $this->assertEquals(is_array($base->getArrayCopy()), true);
    }

    public function testRecordModel()
    {
        $record = new Model\TestRecordModel();

        $attribute = json_encode([
            [
                "id" => 1,
                "name" => "yoyo"
            ]
        ]);

        $record->populate([
            "attribute" => $attribute
        ]);

        $record->record = "2019-12-01";

        $this->assertEquals($record->getRecord(true) instanceof DateTimeInterface, true);
        $this->assertEquals($record->record, "2019-12-01");

        $array = $record->getArrayCopy();
        $this->assertEquals($array["tech_creation_date"], $record->getTechCreationDate());
        $this->assertEquals($array["tech_modification_date"], $record->getTechModificationDate());
    }

    public function testCollectionModel()
    {
        $collection = new Model\TestCollectionModel();
        $collection->setObjectPrototype(Model\TestModel::class);

        $count = 3;
        for ($i = 0; $i < $count; $i++) {
            $model = new Model\TestModel();
            $model->populate([
                "id" => $i,
                "name" => md5($i)
            ]);
            $collection->append($model);
        }


        $this->assertEquals($collection->count(), $count);
        foreach ($collection as $data) {
            $this->assertEquals(($data instanceof Model\TestModel), true);
        }

        $this->assertEquals(count($collection->getArrayColumn("id")), $count);
    }

    public function testParameterCollectionModel()
    {
        $parameterCollection = new ParameterSetModel();
        $parameterCollection->setParameter("test", "testvalue");
        $parameterCollection->append(["new key", "New Value"]);
        $parameterCollection->append(["key" => "MyKey", "value" => "MyValue"]);

        $this->assertEquals($parameterCollection->hasParameter("MyKey"), true);
        $this->assertEquals($parameterCollection->count(), 3);
    }

    public function testComplexModel()
    {
        $complex = new Model\TestComplexModel();

        $data = [
            "id" => 1,
            "name" => "test",
            "data" => [
                [
                    "id" => 123,
                    "name" => "test"
                ]
            ]
        ];

        $complex->populate($data);

        $s = serialize($complex);
        // debug($s);
        // debug(unserialize($s));
        // debug($complex->getArrayForDb());

        $this->assertEquals($complex->data->count(), 1);
        $this->assertEquals($complex->query("data.[0].name"), "test");
        $this->assertEquals(ArrayUtils::query($complex, "data.[0].name"), "test");
        $this->assertEquals(ArrayUtils::query($complex, "data.[0].name.yoyo", "placeholder"), "placeholder");
        $this->assertEquals(ArrayUtils::query($complex, "data.[0].name.yoyo"), null);
        $this->assertEquals($complex->getTechCreationDate(true) instanceof DateTimeInterface, true);
        $this->assertEquals($complex->getTechModificationDate(true) instanceof DateTimeInterface, true);
    }
}
