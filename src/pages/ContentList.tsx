import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { Edit, Trash2, Plus, ArrowLeft } from 'lucide-react';
import { contentAPI, contentTypesAPI } from '../services/api';

interface ContentItem {
  id: number;
  [key: string]: any;
}

interface ContentType {
  id: number;
  name: string;
  label: string;
  slug: string;
  fields?: any[];
}

const ContentList: React.FC = () => {
  const { typeId } = useParams<{ typeId: string }>();
  const navigate = useNavigate();
  
  const [contentItems, setContentItems] = useState<ContentItem[]>([]);
  const [contentType, setContentType] = useState<ContentType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null);
  
  // Фильтры
  const [filters, setFilters] = useState({
    search: '',
    status: '',
  });
  const [isFilterVisible, setIsFilterVisible] = useState(false);
  
  // Загрузка типа контента и его полей
  useEffect(() => {
    const fetchContentType = async () => {
      if (!typeId) return;
      
      try {
        const response = await contentTypesAPI.getById(parseInt(typeId));
        setContentType(response.data.data);
      } catch (err) {
        console.error('Ошибка при загрузке типа контента:', err);
        setError('Не удалось загрузить информацию о типе контента');
      }
    };
    
    fetchContentType();
  }, [typeId]);
  
  // Загрузка записей
  useEffect(() => {
    const fetchContent = async () => {
      if (!typeId) return;
      
      setLoading(true);
      try {
        // Добавляем текущее время в параметры запроса для избежания кэширования
        const response = await contentAPI.getAll(parseInt(typeId), currentPage, 10, {
          ...filters,
          _ts: Date.now() // Добавляем временную метку
        });
        
        if (response.data.success) {
          setContentItems(response.data.data || []);
          setTotalPages(response.data.meta?.total_pages || 1);
        } else {
          setError(response.data.message || 'Ошибка при загрузке данных');
        }
      } catch (err) {
        console.error('Ошибка при загрузке записей:', err);
        setError('Не удалось загрузить записи');
      } finally {
        setLoading(false);
      }
    };
    
    fetchContent();
  }, [typeId, currentPage, filters, window.location.search]); // Добавляем window.location.search в зависимости
  
  const handleDelete = async (id: number) => {
    if (!typeId) return;
    
    try {
      const response = await contentAPI.delete(parseInt(typeId), id);

      if (response.data.success) {
       
        // Обновляем список после удаления
        setContentItems(contentItems.filter(item => item.id !== id));
        setConfirmDelete(null);
      } else {
        setError(response.data.message || 'Ошибка при удалении записи');
      }
    } catch (err) {
      console.error('Ошибка при удалении записи:', err);
      setError('Не удалось удалить запись');
    }
  };
  
  // Функция для отображения значения поля
  const renderFieldValue = (item: ContentItem, fieldName: string) => {
    if (!item || item[fieldName] === undefined) {
      return '-';
    }
    
    const value = item[fieldName];
    
    if (typeof value === 'boolean') {
      return value ? 'Да' : 'Нет';
    }
    
    if (fieldName === 'image' && value) {
      return (
        <img 
          src={value} 
          alt="Превью" 
          className="w-10 h-10 object-cover rounded"
        />
      );
    }
    
    if (typeof value === 'object') {
      return JSON.stringify(value);
    }
    
    return String(value);
  };
  
  // Обработчик смены страницы
  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };
  
  // Обработчик изменения фильтров
  const handleFilterChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
    // Сбрасываем страницу на первую при изменении фильтров
    setCurrentPage(1);
  };
  
  // Применение фильтров
  const applyFilters = (e: React.FormEvent) => {
    e.preventDefault();
    // Фильтры применяются автоматически через useEffect выше
  };
  
  // Сброс фильтров
  const resetFilters = () => {
    setFilters({
      search: '',
      status: '',
    });
    setCurrentPage(1);
  };
  
  if (loading && !contentType) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
          <p className="mt-4 text-gray-500">Загрузка данных...</p>
        </div>
      </div>
    );
  }
  
  if (error) {
    return (
      <div className="p-6 bg-red-50 rounded-lg border border-red-200">
        <h2 className="text-xl font-bold text-red-700 mb-2">Ошибка</h2>
        <p className="text-red-600">{error}</p>
        <button
          onClick={() => navigate(-1)}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
        >
          Вернуться назад
        </button>
      </div>
    );
  }
  
  return (
    <div className="container mx-auto px-4">
      {contentType && (
        <>
          <div className="flex justify-between items-center mb-6">
            <div>
              <button
                onClick={() => navigate(-1)}
                className="flex items-center text-gray-600 hover:text-gray-900 mb-2"
              >
                <ArrowLeft className="w-4 h-4 mr-1" />
                Назад
              </button>
              <h1 className="text-2xl font-bold">{contentType.label}</h1>
              <p className="text-gray-500">{contentType.name}</p>
            </div>
            <div className="flex space-x-2">
              <button
                onClick={() => setIsFilterVisible(!isFilterVisible)}
                className="px-4 py-2 bg-gray-100 text-gray-700 rounded-md flex items-center hover:bg-gray-200"
              >
                {isFilterVisible ? 'Скрыть фильтры' : 'Показать фильтры'}
              </button>
              <Link
                to={`/admin/content-types/${typeId}/content/new`}
                className="px-4 py-2 bg-primary text-black rounded-md flex items-center hover:bg-primary/90"
              >
                <Plus className="w-4 h-4 mr-2" /> Добавить запись
              </Link>
            </div>
          </div>
          
          {/* Фильтры */}
          {isFilterVisible && (
            <div className="bg-white rounded-lg shadow p-4 mb-6">
              <form onSubmit={applyFilters} className="grid md:grid-cols-3 gap-4">
                <div>
                  <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
                    Поиск
                  </label>
                  <input
                    type="text"
                    id="search"
                    name="search"
                    value={filters.search}
                    onChange={handleFilterChange}
                    placeholder="Поиск по заголовку..."
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
                <div>
                  <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-1">
                    Статус
                  </label>
                  <select
                    id="status"
                    name="status"
                    value={filters.status}
                    onChange={handleFilterChange}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                  >
                    <option value="">Все статусы</option>
                    <option value="draft">Черновик</option>
                    <option value="published">Опубликовано</option>
                    <option value="archived">Архив</option>
                  </select>
                </div>
                <div className="flex items-end gap-2">
                  <button
                    type="button"
                    onClick={resetFilters}
                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
                  >
                    Сбросить
                  </button>
                </div>
              </form>
            </div>
          )}
          
          {loading ? (
            <div className="flex items-center justify-center p-12">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              <span className="ml-3">Загрузка...</span>
            </div>
          ) : contentItems.length === 0 ? (
            <div className="bg-gray-50 p-8 rounded-lg text-center">
              <p className="text-gray-500 mb-4">Записи не найдены</p>
              <Link
                to={`/admin/content-types/${typeId}/content/new`}
                className="px-4 py-2 bg-primary text-white rounded-md inline-flex items-center hover:bg-primary/90"
              >
                <Plus className="w-4 h-4 mr-2" /> Создать первую запись
              </Link>
            </div>
          ) : (
            <>
              <div className="overflow-x-auto bg-white rounded-lg shadow">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        ID
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Заголовок
                      </th>
                      {contentType.fields && contentType.fields.slice(0, 3).map(field => (
                        field.name !== 'title' && (
                          <th key={field.id} scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {field.label}
                          </th>
                        )
                      ))}
                      <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Действия
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {contentItems.map((item) => (
                      <tr key={item.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {item.id}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                          {renderFieldValue(item, 'title')}
                        </td>
                        {contentType.fields && contentType.fields.slice(0, 3).map(field => (
                          field.name !== 'title' && (
                            <td key={field.id} className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                              {renderFieldValue(item, field.name)}
                            </td>
                          )
                        ))}
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          {confirmDelete === item.id ? (
                            <div className="flex items-center justify-end space-x-2">
                              <button
                                onClick={() => handleDelete(item.id)}
                                className="text-red-600 hover:text-red-900 px-2 py-1 bg-red-100 rounded"
                              >
                                Да, удалить
                              </button>
                              <button
                                onClick={() => setConfirmDelete(null)}
                                className="text-gray-600 hover:text-gray-900 px-2 py-1 bg-gray-100 rounded"
                              >
                                Отмена
                              </button>
                            </div>
                          ) : (
                            <div className="flex items-center justify-end space-x-2">
                              <Link
                                to={`/admin/content-types/${typeId}/content/${item.id}/edit`}
                                className="px-2 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 flex items-center"
                              >
                                <Edit className="w-4 h-4 mr-1" />
                                Изменить
                              </Link>
                              <button
                                onClick={() => setConfirmDelete(item.id)}
                                className="px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 flex items-center"
                              >
                                <Trash2 className="w-4 h-4 mr-1" />
                                Удалить
                              </button>
                            </div>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              
              {/* Пагинация */}
              {totalPages > 1 && (
                <div className="flex justify-center mt-6">
                  <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <button
                      onClick={() => handlePageChange(currentPage - 1)}
                      disabled={currentPage === 1}
                      className={`relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium ${
                        currentPage === 1 ? 'text-gray-300' : 'text-gray-500 hover:bg-gray-50'
                      }`}
                    >
                      Назад
                    </button>
                    {[...Array(totalPages)].map((_, index) => (
                      <button
                        key={index}
                        onClick={() => handlePageChange(index + 1)}
                        className={`relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium ${
                          currentPage === index + 1
                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                            : 'text-gray-500 hover:bg-gray-50'
                        }`}
                      >
                        {index + 1}
                      </button>
                    ))}
                    <button
                      onClick={() => handlePageChange(currentPage + 1)}
                      disabled={currentPage === totalPages}
                      className={`relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium ${
                        currentPage === totalPages ? 'text-gray-300' : 'text-gray-500 hover:bg-gray-50'
                      }`}
                    >
                      Вперед
                    </button>
                  </nav>
                </div>
              )}
            </>
          )}
        </>
      )}
    </div>
  );
};

export default ContentList; 