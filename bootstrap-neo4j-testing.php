<?php

if(! is_dir("neo4j-community-{$neo4jVersion}")) {
    exec("wget http://neo4j.com/artifact.php?name=neo4j-community-{$neo4jVersion}-unix.tar.gz");
    exec("mv artifact.php?name=neo4j-community-{$neo4jVersion}-unix.tar.gz neo4j-community-{$neo4jVersion}-unix.tar.gz");
    exec("tar xvzf neo4j-community-{$neo4jVersion}-unix.tar.gz");
    exec("rm neo4j-community-{$neo4jVersion}-unix.tar.gz");
    exec("sed -i 's/#org.neo4j.server.webserver.address=0.0.0.0/org.neo4j.server.webserver.address=0.0.0.0/g' neo4j-community-{$neo4jVersion}/conf/neo4j-server.properties");
    exec("sed -i 's/org.neo4j.server.webserver.port=7474/org.neo4j.server.webserver.port=7475/g' neo4j-community-{$neo4jVersion}/conf/neo4j-server.properties");
    exec("sed -i 's/org.neo4j.server.webserver.https.port=7473/org.neo4j.server.webserver.https.port=7476/g' neo4j-community-{$neo4jVersion}/conf/neo4j-server.properties");
    exec("mkdir neo4j-community-".$neo4jVersion."/data/dbms");
    exec('echo "admin:SHA-256,5F5E0B0CEBC8A4FCEEAC6A1E3A778039C7D9912F6C0378CCCBC1F2B1479FCFEA,5DE083E710DD6E7341331DEB11D636D9:" > neo4j-community-'.$neo4jVersion.'/data/dbms/auth');
}

$output = shell_exec("ssh vagrant@local.nekuno.com -o LogLevel=verbose ./../../vagrant/brain/neo4j-community-{$neo4jVersion}/bin/neo4j restart");

if ($output === NULL) {
    trigger_error("Error executing shell_exec");
    die;
}

