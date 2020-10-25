#
# Table structure for table `idiary_config`
#

CREATE TABLE idiary_config (
    entry_list   SMALLINT(2) NOT NULL DEFAULT '0',
    entry_box    SMALLINT(2)          DEFAULT '0',
    html_enabled SMALLINT(2)          DEFAULT '0',
    archives     SMALLINT(2)          DEFAULT '0',
    eventcol     VARCHAR(30)          DEFAULT '0',
    max_fsize    INT(8)               DEFAULT '0',
    max_isize    INT(8)               DEFAULT '0',
    extensions   VARCHAR(255)         DEFAULT '0',
    PRIMARY KEY (entry_list)
)
    ENGINE = ISAM;

#
# Table structure for table `idiary_myholiday`
#

CREATE TABLE idiary_myholiday (
    myholiday VARCHAR(8) NOT NULL DEFAULT '0',
    content   VARCHAR(255)        DEFAULT '0',
    PRIMARY KEY (myholiday)
)
    ENGINE = ISAM;
