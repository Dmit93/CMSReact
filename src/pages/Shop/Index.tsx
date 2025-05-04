import React from 'react';
import { Link } from 'react-router-dom';
import { ShoppingCart, Package, ListOrdered, Settings as SettingsIcon } from 'lucide-react';

const ShopIndex: React.FC = () => {
  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Управление магазином</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link to="/admin/shop/products">
          <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
            <div className="flex items-center mb-4">
              <Package className="h-8 w-8 text-indigo-500 mr-3" />
              <h2 className="text-xl font-semibold">Товары</h2>
            </div>
            <p className="text-gray-600">Управление товарами, добавление, редактирование, удаление товаров</p>
          </div>
        </Link>
        
        <Link to="/admin/shop/categories">
          <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
            <div className="flex items-center mb-4">
              <ShoppingCart className="h-8 w-8 text-indigo-500 mr-3" />
              <h2 className="text-xl font-semibold">Категории</h2>
            </div>
            <p className="text-gray-600">Управление категориями товаров, создание структуры магазина</p>
          </div>
        </Link>
        
        <Link to="/admin/shop/orders">
          <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
            <div className="flex items-center mb-4">
              <ListOrdered className="h-8 w-8 text-indigo-500 mr-3" />
              <h2 className="text-xl font-semibold">Заказы</h2>
            </div>
            <p className="text-gray-600">Просмотр и управление заказами клиентов</p>
          </div>
        </Link>
        
        <Link to="/admin/shop/settings">
          <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
            <div className="flex items-center mb-4">
              <SettingsIcon className="h-8 w-8 text-indigo-500 mr-3" />
              <h2 className="text-xl font-semibold">Настройки</h2>
            </div>
            <p className="text-gray-600">Настройки магазина, валюты, способы доставки и оплаты</p>
          </div>
        </Link>
      </div>
    </div>
  );
};

export default ShopIndex; 