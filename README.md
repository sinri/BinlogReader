# BinlogReader

Now it may parse the binlog file of MySQL 5.6.
Higher versions are not tested yet.

# File Structure of MySQL Binlog File (v4)

1. Magic Words  (`\xfe\x62\x69\x6e`)
2. Events
    * Events start with Format Description Event and End with Rotate Event;
    * Structure of each event
        1. Header (19 bytes)
            1. timestamp (4 bytes)
            2. type code (1 bytes)
            3. server id (4 bytes)
            4. event length (4 bytes)
            5. next position (4 bytes);
            6. flags (2 bytes)
        2. Body (size is event length - checksum size)
            * structure varies amongst different events
        3. Checksum (empty or 4 bytes if CRC32 method used)