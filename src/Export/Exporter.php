<?php

namespace AppScenic\Export;

use AppScenic\AsyncRequests\BackgroundProcess;
use AppScenic\Traits\Webhook;

abstract class Exporter extends BackgroundProcess {

	use Webhook;

}
