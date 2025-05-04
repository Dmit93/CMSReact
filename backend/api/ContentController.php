/**
     * Обновление записи
     * 
     * @param int $contentTypeId ID типа контента
     * @param int $id ID записи
     * @return array Результат операции
     */
    public function update($contentTypeId, $id) {
        try {
            // Проверяем, что контент тип существует
            $contentTypeId = (int)$contentTypeId;
            $id = (int)$id;
            
            error_log("[ContentController.update] Обновление записи. Type ID: {$contentTypeId}, Content ID: {$id}");
            
            if ($contentTypeId <= 0 || $id <= 0) {
                return $this->error('Некорректные параметры запроса');
            }
            
            // Получаем данные запроса
            $data = $this->getRequestData();
            
            // Логируем полученные данные
            error_log("[ContentController.update] Данные запроса: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // Проверяем, что тип контента указан правильно
            if (!isset($data['content_type_id'])) {
                error_log("[ContentController.update] Принудительно устанавливаем content_type_id из URL");
                $data['content_type_id'] = $contentTypeId;
            } elseif ((int)$data['content_type_id'] !== $contentTypeId) {
                error_log("[ContentController.update] Несоответствие content_type_id: передано {$data['content_type_id']}, ожидалось {$contentTypeId}");
                $data['content_type_id'] = $contentTypeId;
            }
            
            // Проверяем наличие ID записи
            if (!isset($data['id'])) {
                error_log("[ContentController.update] Принудительно устанавливаем id из URL");
                $data['id'] = $id;
            } elseif ((int)$data['id'] !== $id) {
                error_log("[ContentController.update] Несоответствие id: передано {$data['id']}, ожидалось {$id}");
                $data['id'] = $id;
            }
            
            // Обновляем запись
            $contentModel = new \Models\ContentModel();
            $result = $contentModel->update($id, $data);
            
            if (!$result['success']) {
                error_log("[ContentController.update] Ошибка обновления: " . $result['message']);
                return $this->error($result['message']);
            }
            
            error_log("[ContentController.update] Успешное обновление записи ID: {$id}");
            return $this->success($result['data'], 'Запись успешно обновлена');
            
        } catch (\Exception $e) {
            error_log("[ContentController.update] Исключение: " . $e->getMessage());
            return $this->error('Ошибка при обновлении записи: ' . $e->getMessage());
        }
    } 