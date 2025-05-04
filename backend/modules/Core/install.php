<?php
/**
 * Скрипт установки модуля Core
 */

namespace Modules\Core;

use Core\Logger;

// Логируем процесс установки
Logger::getInstance()->info('Installing Core module');

// Здесь можно выполнять любые действия при установке

// Всегда возвращаем успешный результат для базового модуля
return ['success' => true, 'message' => 'Базовый модуль Core успешно установлен']; 