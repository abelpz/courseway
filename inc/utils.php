<?php
foreach (glob("inc/utils/*.php") as $filename) {
    include $filename;
}