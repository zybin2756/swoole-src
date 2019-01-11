--TEST--
swoole_redis_coro: redis psubscribe eof 2
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';
$sock = new Swoole\Coroutine\Socket(AF_INET, SOCK_STREAM, 0);
$sock->bind('127.0.0.1');
$info = $sock->getsockname();
$port = $info['port'];
go(function () use ($sock) {
    $sock->listen();

    while ($client = $sock->accept(-1)) {
        $client->recv();
        $client->send("*3\r\n\$10\r\npsubscribe\r\n\$8\r\nchannel1\r\n:1\r\n");
        co::sleep(0.1);
        $client->close();
    }

    echo "DONE\n";
});
go(function () use ($sock, $port) {
    $redis = new Swoole\Coroutine\Redis();
    $redis->connect('127.0.0.1', $port);

    $val = $redis->psubscribe(['channel1']);
    assert($val === true);

    $val = $redis->recv();
    assert($val === false);

    assert($redis->connected === false);
    assert($redis->errType === SWOOLE_REDIS_ERR_EOF);

    $redis->close();
    $sock->close();
});
swoole_event_wait();
?>
--EXPECT--
DONE
