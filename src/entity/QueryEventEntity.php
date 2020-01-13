<?php


namespace sinri\BinlogReader\entity;


use sinri\BinlogReader\BRKit;

class QueryEventEntity extends BaseBinlogV4EventEntity
{

    const STATUS_VAR_KEY_Q_FLAGS2_CODE=0x00;
    const STATUS_VAR_KEY_Q_SQL_MODE_CODE=0x01;
    const STATUS_VAR_KEY_Q_CATALOG=0x02;
    const STATUS_VAR_KEY_Q_AUTO_INCREMENT=0x03;
    const STATUS_VAR_KEY_Q_CHARSET_CODE=0x04;
    const STATUS_VAR_KEY_Q_TIME_ZONE_CODE=0x05;
    const STATUS_VAR_KEY_Q_CATALOG_NZ_CODE=0x06;
    const STATUS_VAR_KEY_Q_LC_TIME_NAMES_CODE=0x07;
    const STATUS_VAR_KEY_Q_CHARSET_DATABASE_CODE=0x08;
    const STATUS_VAR_KEY_Q_TABLE_MAP_FOR_UPDATE_CODE=0x09;
    const STATUS_VAR_KEY_Q_MASTER_DATA_WRITTEN_CODE=0x0a;
    const STATUS_VAR_KEY_Q_INVOKERS=0x0b;
    const STATUS_VAR_KEY_Q_UPDATED_DB_NAMES=0x0c;
    const STATUS_VAR_KEY_Q_MICROSECONDS=0x0d;

    public $slaveProxyId;
    public $executionTime;
    public $schemaLength;
    public $errorCode;
    /**
     * @var int number of bytes in the following sequence of status-vars
     */
    public $statusVarsLength;
    /**
     * the definition of items @see https://dev.mysql.com/doc/internals/en/query-event.html
     * @var array status key-value pair
     */
    public $statusVars;
    public $schema;
    public $query;

    /**
     * @inheritDoc
     */
    public function readFromBinlogStream($reader)
    {
        $reader->getLogger()->debug(__METHOD__ . '@' . __LINE__, ['method' => $this->method, 'version' => $this->version]);

//        $this->debugShowBody($reader);
//        return;

        /*
         * 000000	| 6e(n) 1a() 7f() 00( )|00( ) 00( ) 00( ) 00( )|00( )|00( )
         * 000010	| 00( )|22(") 00( )|00( )→00( ) 00( ) 00( ) 00( )|01()→00( ) // 7
         * 000020	| 00( ) 20( ) 00( ) 00( ) 00( ) 00( ) 00( )|06()→03()→73(s) // 10
         * 000030	| 74(t) 64(d)|04()→21(!) 00( ) 21(!) 00( ) 21(!) 00( )|0c()→ //10
         * 000040	| 01()→6d(m) 79(y) 73(s) 71(q) 6c(l) 00( )|00( )|2f(/) 2a(*) // 7
         * 000050	| 20( ) 72(r) 64(d) 73(s) 20( ) 69(i) 6e(n) 74(t) 65(e) 72(r)
         * 000060	| 6e(n) 61(a) 6c(l) 20( ) 6d(m) 61(a) 72(r) 6b(k) 20( ) 2a(*)
         * 000070	| 2f(/) 20( ) 43(C) 52(R) 45(E) 41(A) 54(T) 45(E) 20( ) 54(T)
         * 000080	| 41(A) 42(B) 4c(L) 45(E) 20( ) 49(I) 46(F) 20( ) 4e(N) 4f(O)
         * 000090	| 54(T) 20( ) 45(E) 58(X) 49(I) 53(S) 54(T) 53(S) 20( ) 6d(m)
         * 000100	| 79(y) 73(s) 71(q) 6c(l) 2e(.) 68(h) 61(a) 5f(_) 68(h) 65(e)
         * 000110	| 61(a) 6c(l) 74(t) 68(h) 5f(_) 63(c) 68(h) 65(e) 63(c) 6b(k)
         * 000120	| 20( ) 28(() 0a(↓) 20( ) 20( ) 69(i) 64(d) 20( ) 20( ) 20( )
         * 000130	| 42(B) 49(I) 47(G) 49(I) 4e(N) 54(T) 20( ) 20( ) 44(D) 45(E)
         * 000140	| 46(F) 41(A) 55(U) 4c(L) 54(T) 20( ) 30(0) 2c(,) 0a(↓) 20( )
         * 000150	| 20( ) 74(t) 79(y) 70(p) 65(e) 20( ) 43(C) 48(H) 41(A) 52(R)
         * 000160	| 28(() 31(1) 29()) 20( ) 44(D) 45(E) 46(F) 41(A) 55(U) 4c(L)
         * 000170	| 54(T) 20( ) 27(') 30(0) 27(') 2c(,) 0a(↓) 20( ) 20( ) 50(P)
         * 000180	| 52(R) 49(I) 4d(M) 41(A) 52(R) 59(Y) 20( ) 4b(K) 45(E) 59(Y)
         * 000190	| 20( ) 28(() 74(t) 79(y) 70(p) 65(e) 29()) 0a(↓) 29()) 0a(↓)
         * 000200	| 20( ) 20( ) 45(E) 4e(N) 47(G) 49(I) 4e(N) 45(E) 20( ) 3d(=)
         * 000210	| 20( ) 49(I) 6e(n) 6e(n) 6f(o) 44(D) 42(B)|93(�) ba(�) 7d(})
         * 000220	| 91(�)
         */

        $this->slaveProxyId = $reader->readNumber(4);
        $this->executionTime = $reader->readNumber(4);
        $this->schemaLength = $reader->readNumber(1);
        $this->errorCode = $reader->readNumber(2);
        $this->statusVarsLength = $reader->readNumber(2);

//        var_dump($this->statusVarsLength);

        $realStatusBytes = 0;
        while($realStatusBytes<$this->statusVarsLength){
            $key=$reader->readNumber(1);
            $realStatusBytes+=1;
            switch ($key) {
                case self::STATUS_VAR_KEY_Q_FLAGS2_CODE:
                    // Bitmask of flags that are usually set with SET:
                case self::STATUS_VAR_KEY_Q_MASTER_DATA_WRITTEN_CODE:
                    $value = $reader->readNumber(4);
                    $realStatusBytes+=4;
                    break;
                case self::STATUS_VAR_KEY_Q_SQL_MODE_CODE:
                    // Bitmask of flags that are usually set with SET sql_mode:
                case self::STATUS_VAR_KEY_Q_TABLE_MAP_FOR_UPDATE_CODE:
                    // a 64bit-field ... should only be used in Row Based Replication and multi-table updates
                    $value = $reader->readNumber(8);
                    $realStatusBytes+=8;
                    break;
                case self::STATUS_VAR_KEY_Q_CATALOG:
                    $catalogLength=$reader->readNumber(1);
                    $catalogName=$reader->readString($catalogLength);
                    $zeroChar=$reader->readNumber(1);// \0 as EOF
                    $value=$catalogName;
                    $realStatusBytes+=1+$catalogLength+1;
                    break;
                case self::STATUS_VAR_KEY_Q_AUTO_INCREMENT:
                    $value=[
                        'autoincrement-increment'=>$reader->readNumber(2),
                        'autoincrement-offset'=>$reader->readNumber(2),
                    ];
                    $realStatusBytes+=4;
                    break;
                case self::STATUS_VAR_KEY_Q_CHARSET_CODE:
                    // @see Connection Character Sets and Collations https://dev.mysql.com/doc/refman/8.0/en/charset-connection.html
                    $value=[
                        'character_set_client'=>$reader->readNumber(2),
                        'collation_connection'=>$reader->readNumber(2),
                        'collation_server'=>$reader->readNumber(2),
                    ];
                    $realStatusBytes+=6;
                    break;
                case self::STATUS_VAR_KEY_Q_TIME_ZONE_CODE:
                    //@see MySQL Server Time Zone Support https://dev.mysql.com/doc/refman/8.0/en/time-zone-support.html
                    $timezoneLength=$reader->readNumber(1);
                    $value=$reader->readString($timezoneLength);
                    $realStatusBytes+=1+$timezoneLength;
                    break;
                case self::STATUS_VAR_KEY_Q_CATALOG_NZ_CODE:
                    $categoryNZCodeLength=$reader->readNumber(1);
                    $value=$reader->readString($categoryNZCodeLength);
                    $realStatusBytes+=1+$categoryNZCodeLength;
                    break;
                case self::STATUS_VAR_KEY_Q_LC_TIME_NAMES_CODE:
                case self::STATUS_VAR_KEY_Q_CHARSET_DATABASE_CODE:
                    $value = $reader->readNumber(2);
                    $realStatusBytes+=2;
                    break;
                case self::STATUS_VAR_KEY_Q_INVOKERS:
                    $usernameLength=$reader->readNumber(1);
                    $username=$reader->readString($usernameLength);
                    $hostnameLength=$reader->readNumber(1);
                    $hostname=$reader->readString($hostnameLength);
                    $value=[
                        'username'=>$username,
                        'hostname'=>$hostname,
                    ];
                    $realStatusBytes+=1+$usernameLength+1+$hostnameLength;
                    break;
                case self::STATUS_VAR_KEY_Q_UPDATED_DB_NAMES:
                    $dbNameLength = $reader->readNumber(1);
                    $realStatusBytes += 1;
                    $value = "";
                    for ($i = 0; $i < $dbNameLength; $i++) {
                        $value .= $reader->readStringEndedWithZero();
                        $realStatusBytes += 1 + strlen($value);
                    }
                    break;
                case self::STATUS_VAR_KEY_Q_MICROSECONDS:
                    $value = $reader->readNumber(3);
                    $realStatusBytes+=3;
                    break;
                default:
                    throw new \Exception(__METHOD__.'@'.__LINE__.' invalid status var key '.$key);
            }
//            var_dump($key);
//            var_dump($value);
            $this->statusVars[$key]=$value;
        }

        if($this->schemaLength>0) {
            $this->schema = $reader->readString($this->schemaLength);
        }

        $reader->readNumber(1);
        //$reader->getLogger()->debug(__METHOD__.'@'.__LINE__." temp=".$temp.' <-- must be 0x00');

        $this->query='';
        $queryLength=$this->header->eventLength-19-$this->schemaLength-$realStatusBytes-1-4-4-1-2-2;
        $queryLength-=self::checksumByteCount();// for tail crc32
        if($queryLength>0) {
            //$this->query = base64_encode($reader->readString($queryLength));
            // TODO "BEGIN|0xf0||0xec||0xb1||0xb8|" the last four bytes strange => should verify if crc32 4 bytes

            for ($i=0;$i<$queryLength;$i++){
                $charOrd=$reader->readNumber(1);
                if($charOrd<128) {
                    $char = chr($charOrd);
                    $this->query .= $char;
                }else{
                    $this->query .= "|0x".dechex($charOrd)."|";
                }
            }
        }

        //var_dump($this);
    }

    public function getHumanReadableDescription()
    {
        return "Slave Proxy ID: ".$this->slaveProxyId.' '
            .'Execution Time: '.BRKit::refinedTime($this->executionTime).' '
            .'Error Code: '.$this->errorCode.PHP_EOL
            .'Schema: '.$this->schema.PHP_EOL
            .'Query: '.$this->query;
    }
}