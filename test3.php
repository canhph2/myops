<?php

exec("export TEST1=TEST1VAL");
echo "test1=".getenv('TEST1');
