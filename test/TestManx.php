<?php

require_once 'pages/Manx.php';

class TestManx extends PHPUnit\Framework\TestCase
{
    /** @var IManxDatabase */
    private $_db;
    /** @var Manx */
    private $_manx;

    protected function setUp()
    {
        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_manx = Manx::getInstanceForDatabase($this->_db);
    }

    public function testConstruct()
    {
        $this->assertTrue(!is_null($this->_manx) && is_object($this->_manx));
    }

    public function testAddPublication()
    {
        $userId = 333;
        $user = $this->createMock(IUser::class);
        $user->expects($this->once())->method('userId')->willReturn($userId);
        $companyId = 10;
        $part = "EK-1011-01";
        $title = "User's Guide For Fictional Device";
        $pubType = 'D';
        $altPart = "KE-0100-10";
        $revision = 'Rev. A';
        $pubDate = '1978';
        $keywords = "foo bar";
        $notes = "notes";
        $abstract = "abstract";
        $languages = "en fr";
        $pubHistId = 20;
        $this->_db->expects($this->once())->method('addPubHistory')
            ->with($userId, $pubType, $companyId, $part, $altPart,
                $revision, $pubDate, $title, $keywords, $notes, $abstract, $languages)
            ->willReturn($pubHistId);
        $pubId = 30;
        $this->_db->expects($this->once())->method('addPublication')->with($pubHistId)->willReturn($pubId);
        $this->_db->expects($this->once())->method('updatePubHistoryPubId')->with($pubHistId, $pubId);

        $result = $this->_manx->addPublication($user,
            $companyId,
            $part,
            $pubDate,
            $title,
            $pubType,
            $altPart,
            $revision,
            $keywords,
            $notes,
            $abstract,
            $languages);

        $this->assertEquals($pubId, $result);
    }
}
