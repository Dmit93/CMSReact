import React, { useState, useEffect } from 'react';
import { Search, Eye, Download, Clock, Package, CheckCircle, AlertTriangle, XCircle } from 'lucide-react';

// Заглушка для API заказов
const sampleOrders = [
  { 
    id: 10052, 
    customer: 'Иванов Иван', 
    email: 'ivanov@example.com',
    date: '2023-12-15T14:32:00', 
    total: 15800, 
    items: 3, 
    status: 'completed',
    payment_status: 'paid',
    shipping_method: 'Курьер' 
  },
  { 
    id: 10051, 
    customer: 'Петров Петр', 
    email: 'petrov@example.com',
    date: '2023-12-14T09:45:00', 
    total: 7600, 
    items: 1, 
    status: 'processing',
    payment_status: 'paid',
    shipping_method: 'Самовывоз' 
  },
  { 
    id: 10050, 
    customer: 'Сидорова Елена', 
    email: 'sidorova@example.com',
    date: '2023-12-12T18:20:00', 
    total: 32450, 
    items: 5, 
    status: 'completed',
    payment_status: 'paid',
    shipping_method: 'Почта России' 
  },
  { 
    id: 10049, 
    customer: 'Козлов Дмитрий', 
    email: 'kozlov@example.com',
    date: '2023-12-10T11:15:00', 
    total: 9900, 
    items: 2, 
    status: 'cancelled',
    payment_status: 'refunded',
    shipping_method: 'Курьер' 
  },
  { 
    id: 10048, 
    customer: 'Новикова Анна', 
    email: 'novikova@example.com',
    date: '2023-12-08T16:40:00', 
    total: 18750, 
    items: 4, 
    status: 'shipped',
    payment_status: 'paid',
    shipping_method: 'СДЭК' 
  }
];

// Функция форматирования даты
const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};

// Функция получения статуса заказа
const getStatusDisplay = (status: string) => {
  switch (status) {
    case 'completed':
      return {
        text: 'Выполнен',
        color: 'bg-green-100 text-green-800',
        icon: <CheckCircle className="w-4 h-4 mr-1" />
      };
    case 'processing':
      return {
        text: 'В обработке',
        color: 'bg-blue-100 text-blue-800',
        icon: <Clock className="w-4 h-4 mr-1" />
      };
    case 'shipped':
      return {
        text: 'Отправлен',
        color: 'bg-indigo-100 text-indigo-800',
        icon: <Package className="w-4 h-4 mr-1" />
      };
    case 'cancelled':
      return {
        text: 'Отменен',
        color: 'bg-red-100 text-red-800',
        icon: <XCircle className="w-4 h-4 mr-1" />
      };
    default:
      return {
        text: 'Неизвестен',
        color: 'bg-gray-100 text-gray-800',
        icon: <AlertTriangle className="w-4 h-4 mr-1" />
      };
  }
};

const ShopOrders: React.FC = () => {
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');

  useEffect(() => {
    // Имитация загрузки данных
    const loadData = () => {
      setTimeout(() => {
        setOrders(sampleOrders);
        setLoading(false);
      }, 500);
    };

    loadData();
  }, []);

  // Фильтрация заказов по поисковому запросу и статусу
  const filteredOrders = orders.filter(order => {
    const matchesSearch = searchTerm === '' || 
      order.customer.toLowerCase().includes(searchTerm.toLowerCase()) || 
      order.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      order.id.toString().includes(searchTerm);
    
    const matchesStatus = statusFilter === 'all' || order.status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  // Обработчик просмотра заказа
  const handleViewOrder = (id: number) => {
    alert(`Просмотр заказа с ID: ${id}`);
  };

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Заказы</h1>

      <div className="mb-6 flex flex-col md:flex-row space-y-3 md:space-y-0 md:space-x-3">
        {/* Поиск */}
        <div className="relative flex-grow">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Поиск по имени, email или номеру заказа..."
            className="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        {/* Фильтр по статусу */}
        <div className="md:w-64">
          <select
            className="w-full p-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="all">Все статусы</option>
            <option value="processing">В обработке</option>
            <option value="shipped">Отправлен</option>
            <option value="completed">Выполнен</option>
            <option value="cancelled">Отменен</option>
          </select>
        </div>
      </div>

      {/* Таблица заказов */}
      {loading ? (
        <div className="flex justify-center items-center p-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
        </div>
      ) : filteredOrders.length === 0 ? (
        <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
          <p className="flex items-center">
            <AlertTriangle className="mr-2" />
            Заказы не найдены. Попробуйте изменить параметры поиска или фильтрации.
          </p>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  №
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Дата
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Клиент
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Сумма
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Статус
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Действия
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredOrders.map((order) => {
                const status = getStatusDisplay(order.status);
                return (
                  <tr key={order.id}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">№{order.id}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-500">{formatDate(order.date)}</div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm font-medium text-gray-900">{order.customer}</div>
                      <div className="text-sm text-gray-500">{order.email}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900">{order.total.toLocaleString()} ₽</div>
                      <div className="text-sm text-gray-500">{order.items} товаров</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`flex items-center px-2 py-1 text-xs leading-5 font-semibold rounded-full ${status.color}`}>
                        {status.icon}
                        {status.text}
                      </span>
                      <div className="text-xs text-gray-500 mt-1">
                        {order.shipping_method}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button 
                        className="text-indigo-600 hover:text-indigo-900 mr-3"
                        onClick={() => handleViewOrder(order.id)}
                      >
                        <Eye className="w-4 h-4" />
                      </button>
                      <button 
                        className="text-green-600 hover:text-green-900"
                      >
                        <Download className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default ShopOrders; 