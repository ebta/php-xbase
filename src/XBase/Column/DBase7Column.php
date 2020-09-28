<?php

namespace XBase\Column;

use XBase\Stream\Stream;
use XBase\Stream\StreamWrapper;

class DBase7Column extends AbstractColumn
{
    public static function getHeaderLength(): int
    {
        return 48;
    }

    public static function create(string $memoryChunk, int $colIndex, ?int $bytePos = null)
    {
        if (strlen($memoryChunk) !== self::getHeaderLength()) {
            throw new \LogicException('Column data expected length: '.self::getHeaderLength());
        }

        $s = Stream::createFromString($memoryChunk);

        return new self(
            $s->read(32),
            $s->read(1),//type
            $s->readUChar(),//length
            $s->readUChar(),//decimalCount
            $s->readUShort(),//reserved1
            $s->readUChar(),//mdxFlag
            $s->readUShort(),//reserved2
            $s->readUInt(),//nextAI
            $s->read(4),//reserved3
            $colIndex,
            $bytePos
        );
    }

    /** @var int */
    protected $mdxFlag;
    /** @var int */
    protected $nextAI;

    /**
     * @var string $name         Field name in ASCII (zero-filled).
     * @var string $type         Field type in ASCII (B, C, D, N, L, M, @, I, +, F, 0 or G).
     * @var int    $length       Field length in binary.
     * @var int    $decimalCount Field decimal count in binary.
     * @var mixed  $reserved1    Reserved.
     * @var int    $mdxFlag      Production .MDX field flag; 0x01 if field has an index tag in the production .MDX file; 0x00 if the field is not indexed.
     * @var mixed  $reserved2    Reserved.
     * @var int    $nextAI       Next Autoincrement value, if the Field type is Autoincrement, 0x00 otherwise.
     * @var mixed  $reserved3    Reserved.
     */
    public function __construct(string $name, string $type, int $length, int $decimalCount, $reserved1, int $mdxFlag, $reserved2, int $nextAI, $reserved3, int $colIndex, ?int $bytePos = null)
    {
        $this->rawName = $name;
        $this->name = strtolower(rtrim($name, chr(0x00)));
        $this->type = $type;
        $this->length = $length;
        $this->decimalCount = $decimalCount;
        $this->mdxFlag = $mdxFlag;
        $this->nextAI = $nextAI;
        // not protocol
        $this->colIndex = $colIndex;
        $this->bytePos = $bytePos;
    }

    public function toBinaryString(StreamWrapper $fp): void
    {
        $fp->write($this->rawName);
        $fp->write($this->type);
        $fp->writeUChar($this->length);
        $fp->writeUChar($this->decimalCount);
        $fp->write(str_pad('', 2, chr(0)));
        $fp->writeUChar($this->mdxFlag);
        $fp->write(str_pad('', 2, chr(0)));
        $fp->writeInt($this->nextAI);
        $fp->write(str_pad('', 4, chr(0)));
    }
}
