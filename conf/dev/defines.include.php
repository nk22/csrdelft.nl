<?php

# -------------------------------------------------------------------
# defines.include.php
# -------------------------------------------------------------------
# Voorbeeldinstellingen voor een development stek, zie ook defines.defaults.php
# -------------------------------------------------------------------
#
define('DB_CHECK', true);
define('DB_MODIFY', false);
define('DB_DROP', false);
define('DEBUG', true);
define('TIME_MEASURE', true);
define('FORCE_HTTPS', false);
define('SERVER_PORT', ':8080');
define('CSR_DOMAIN', 'localhost');
define('BASE_PATH', '/app/'); # Zet mij.
define('CSR_ROOT', 'http://' . CSR_DOMAIN . SERVER_PORT);
define('IMAGEMAGICK', 'convert');
