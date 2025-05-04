#!/bin/bash

# Генерируем уникальный SKU
SKU="TEST-$(date +%s)"

# Создаем данные товара
DATA="{
  \"title\": \"CURL Test Product $(date +%Y-%m-%d:%H:%M:%S)\",
  \"sku\": \"$SKU\",
  \"price\": 99.99,
  \"stock\": 10,
  \"status\": \"published\",
  \"description\": \"Товар создан через CURL-запрос\"
}"

echo "Отправляем данные:"
echo $DATA

# Отправляем POST-запрос на создание товара
echo -e "\nОтправка запроса на http://localhost/cms/backend/api/shop/products..."
curl -X POST \
  -H "Content-Type: application/json" \
  -d "$DATA" \
  "http://localhost/cms/backend/api/shop/products" \
  -v 