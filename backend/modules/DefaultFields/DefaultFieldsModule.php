<?php
namespace Modules\DefaultFields;

use Core\BaseModule;
use Core\EventManager;
use Core\Logger;
use Models\ContentTypeFieldModel;

/**
 * Модуль для добавления стандартных полей к типам контента
 */
class DefaultFieldsModule extends BaseModule {
    /**
     * Имя модуля
     */
    protected $name = 'DefaultFields';
    
    /**
     * Версия модуля
     */
    protected $version = '1.0.0';
    
    /**
     * Описание модуля
     */
    protected $description = 'Автоматически добавляет стандартные поля к типам контента';
    
    /**
     * Автор модуля
     */
    protected $author = 'Дмитрий Н.А';
    
    /**
     * Логгер
     */
    private $logger;
    
    /**
     * Модель полей типа контента
     */
    private $fieldModel;
    
    /**
     * Инициализация модуля
     */
    public function init() {
        $this->logger = Logger::getInstance();
        $this->fieldModel = new ContentTypeFieldModel();
        
        // Подключаемся к событию после создания типа контента
        $this->registerEventHandler('content_type.after_save', [$this, 'onContentTypeSave']);
        
        $this->logger->info('DefaultFieldsModule initialized');
    }
    
    /**
     * Обработчик события сохранения типа контента
     */
    public function onContentTypeSave($params) {
        // Проверяем, является ли это новым типом контента
        if (!isset($params['isNew']) || !$params['isNew'] || !isset($params['contentType'])) {
            return null;
        }
        
        $contentType = $params['contentType'];
        $contentTypeId = $contentType['id'];
        
        $this->logger->info('Creating default fields for content type', [
            'contentTypeId' => $contentTypeId,
            'contentTypeName' => $contentType['name']
        ]);
        
        // Добавляем стандартные поля
        $this->addDefaultFields($contentTypeId);
        
        return null;
    }
    
    /**
     * Добавление стандартных полей к типу контента
     */
    private function addDefaultFields($contentTypeId) {
        $defaultFields = $this->getDefaultFields();
        
        foreach ($defaultFields as $order => $field) {
            // Добавляем ID типа контента и порядок
            $field['content_type_id'] = $contentTypeId;
            $field['order'] = $order;
            
            // Проверяем, существует ли поле с таким именем
            $existingField = $this->fieldModel->getByName($contentTypeId, $field['name']);
            
            if (!$existingField) {
                // Создаем поле, если оно еще не существует
                $result = $this->fieldModel->create($field);
                
                if ($result['success']) {
                    $this->logger->info('Default field created', [
                        'fieldName' => $field['name'],
                        'contentTypeId' => $contentTypeId,
                        'fieldId' => $result['data']['id']
                    ]);
                } else {
                    $this->logger->error('Failed to create default field', [
                        'fieldName' => $field['name'],
                        'contentTypeId' => $contentTypeId,
                        'error' => $result['message']
                    ]);
                }
            } else {
                $this->logger->info('Default field already exists', [
                    'fieldName' => $field['name'],
                    'contentTypeId' => $contentTypeId,
                    'fieldId' => $existingField['id']
                ]);
            }
        }
    }
    
    /**
     * Получение списка стандартных полей
     */
    private function getDefaultFields() {
        return [
            // Заголовок
            [
                'name' => 'title',
                'label' => 'Заголовок',
                'description' => 'Заголовок записи',
                'field_type' => 'text',
                'required' => 1,
                'is_required' => 1,
                'options' => json_encode([
                    'max_length' => 255,
                    'placeholder' => 'Введите заголовок'
                ]),
                'validation' => json_encode([
                    'required' => true
                ])
            ],
            
            // Описание
            [
                'name' => 'description',
                'label' => 'Описание',
                'description' => 'Краткое описание или аннотация',
                'field_type' => 'textarea',
                'required' => 0,
                'is_required' => 0,
                'options' => json_encode([
                    'rows' => 5,
                    'placeholder' => 'Введите описание'
                ])
            ],
            
            // Изображение
            [
                'name' => 'image',
                'label' => 'Изображение',
                'description' => 'Основное изображение',
                'field_type' => 'image',
                'required' => 0,
                'is_required' => 0,
                'options' => json_encode([
                    'max_size' => 2048, // 2MB
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
                ])
            ]
        ];
    }
} 