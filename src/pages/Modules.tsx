import React, { useState, useEffect } from 'react';
import { modulesAPI } from '../services/api';
import { Package, Check, X, Download, Trash, AlertCircle } from 'lucide-react';

// Интерфейс для модуля
interface Module {
  id: number;
  name: string;
  slug: string;
  description: string;
  status: 'active' | 'inactive';
  version: string;
  installed_at: string | null;
  created_at: string;
  updated_at: string;
}

const Modules: React.FC = () => {
  const [modules, setModules] = useState<Module[]>([]);
  const [availableModules, setAvailableModules] = useState<{id: string, name: string, description: string, version: string}[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionInProgress, setActionInProgress] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  // Загрузка модулей при монтировании компонента
  useEffect(() => {
    fetchModules();
  }, []);

  // Функция для загрузки установленных модулей
  const fetchModules = async () => {
    setLoading(true);
    try {
      const response = await modulesAPI.getAll();
      console.log('Загруженные модули:', response.data);
      
      // Установленные модули
      if (response.data.installed) {
        setModules(response.data.installed || []);
      }
      
      // Доступные для установки модули
      if (response.data.available) {
        setAvailableModules(response.data.available || []);
      }
      
      setLoading(false);
    } catch (err) {
      console.error('Ошибка при загрузке модулей:', err);
      setError('Не удалось загрузить список модулей');
      setLoading(false);
    }
  };

  // Функция для активации модуля
  const activateModule = async (slug: string) => {
    setActionInProgress(slug);
    try {
      const response = await modulesAPI.activate(slug);
      if (response.data.success) {
        setSuccessMessage(`Модуль ${slug} успешно активирован`);
        // Обновляем список модулей
        fetchModules();
      } else {
        setError(response.data.message || 'Ошибка при активации модуля');
      }
    } catch (err: any) {
      console.error('Ошибка при активации модуля:', err);
      setError(err.response?.data?.message || 'Ошибка при активации модуля');
    } finally {
      setActionInProgress(null);
      // Скрываем сообщение об успехе через 3 секунды
      if (successMessage) {
        setTimeout(() => setSuccessMessage(null), 3000);
      }
    }
  };

  // Функция для деактивации модуля
  const deactivateModule = async (slug: string) => {
    setActionInProgress(slug);
    try {
      const response = await modulesAPI.deactivate(slug);
      if (response.data.success) {
        setSuccessMessage(`Модуль ${slug} успешно деактивирован`);
        // Обновляем список модулей
        fetchModules();
      } else {
        setError(response.data.message || 'Ошибка при деактивации модуля');
      }
    } catch (err: any) {
      console.error('Ошибка при деактивации модуля:', err);
      setError(err.response?.data?.message || 'Ошибка при деактивации модуля');
    } finally {
      setActionInProgress(null);
      // Скрываем сообщение об успехе через 3 секунды
      if (successMessage) {
        setTimeout(() => setSuccessMessage(null), 3000);
      }
    }
  };

  // Функция для установки модуля
  const installModule = async (slug: string) => {
    setActionInProgress(slug);
    try {
      const response = await modulesAPI.install(slug);
      if (response.data.success) {
        setSuccessMessage(`Модуль ${slug} успешно установлен`);
        // Обновляем список модулей
        fetchModules();
      } else {
        setError(response.data.message || 'Ошибка при установке модуля');
      }
    } catch (err: any) {
      console.error('Ошибка при установке модуля:', err);
      setError(err.response?.data?.message || 'Ошибка при установке модуля');
    } finally {
      setActionInProgress(null);
      // Скрываем сообщение об успехе через 3 секунды
      if (successMessage) {
        setTimeout(() => setSuccessMessage(null), 3000);
      }
    }
  };

  // Функция для удаления модуля
  const uninstallModule = async (slug: string) => {
    if (!window.confirm(`Вы уверены, что хотите удалить модуль ${slug}?`)) {
      return;
    }
    
    setActionInProgress(slug);
    try {
      const response = await modulesAPI.uninstall(slug);
      if (response.data.success) {
        setSuccessMessage(`Модуль ${slug} успешно удален`);
        // Обновляем список модулей
        fetchModules();
      } else {
        setError(response.data.message || 'Ошибка при удалении модуля');
      }
    } catch (err: any) {
      console.error('Ошибка при удалении модуля:', err);
      setError(err.response?.data?.message || 'Ошибка при удалении модуля');
    } finally {
      setActionInProgress(null);
      // Скрываем сообщение об успехе через 3 секунды
      if (successMessage) {
        setTimeout(() => setSuccessMessage(null), 3000);
      }
    }
  };

  // Очистка сообщения об ошибке
  const clearError = () => {
    setError(null);
  };

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Управление модулями</h1>
      
      {/* Сообщение об ошибке */}
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
          <span className="block sm:inline">{error}</span>
          <span className="absolute top-0 bottom-0 right-0 px-4 py-3" onClick={clearError}>
            <X size={18} className="cursor-pointer" />
          </span>
        </div>
      )}
      
      {/* Сообщение об успехе */}
      {successMessage && (
        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
          <span className="block sm:inline">{successMessage}</span>
        </div>
      )}
      
      {/* Секция установленных модулей */}
      <div className="mb-8">
        <h2 className="text-xl font-semibold mb-4">Установленные модули</h2>
        
        {loading ? (
          <div className="flex justify-center items-center p-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>
        ) : modules.length === 0 ? (
          <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
            <div className="flex items-center">
              <AlertCircle size={20} className="mr-2" />
              <span>Нет установленных модулей</span>
            </div>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Имя
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Описание
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Версия
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
                {modules.map((module) => (
                  <tr key={module.id}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <Package size={18} className="mr-2 text-gray-500" />
                        <div className="text-sm font-medium text-gray-900">{module.name}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm text-gray-500">{module.description}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-500">{module.version}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                        module.status === 'active' 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-yellow-100 text-yellow-800'
                      }`}>
                        {module.status === 'active' ? 'Активен' : 'Неактивен'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      {module.status === 'active' ? (
                        <button 
                          className="text-indigo-600 hover:text-indigo-900 mr-3"
                          onClick={() => deactivateModule(module.slug)}
                          disabled={actionInProgress === module.slug}
                        >
                          {actionInProgress === module.slug ? (
                            <span className="flex items-center">
                              <span className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mr-1"></span>
                              Деактивация...
                            </span>
                          ) : (
                            <span>Деактивировать</span>
                          )}
                        </button>
                      ) : (
                        <button 
                          className="text-green-600 hover:text-green-900 mr-3"
                          onClick={() => activateModule(module.slug)}
                          disabled={actionInProgress === module.slug}
                        >
                          {actionInProgress === module.slug ? (
                            <span className="flex items-center">
                              <span className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mr-1"></span>
                              Активация...
                            </span>
                          ) : (
                            <span>Активировать</span>
                          )}
                        </button>
                      )}
                      
                      <button 
                        className="text-red-600 hover:text-red-900"
                        onClick={() => uninstallModule(module.slug)}
                        disabled={actionInProgress === module.slug || module.status === 'active'}
                      >
                        {actionInProgress === module.slug ? (
                          <span className="flex items-center">
                            <span className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mr-1"></span>
                            Удаление...
                          </span>
                        ) : (
                          <span>Удалить</span>
                        )}
                      </button>
                      
                      {module.status === 'active' && (
                        <div className="text-xs text-gray-500 mt-1">
                          Для удаления сначала деактивируйте модуль
                        </div>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
      
      {/* Секция доступных для установки модулей */}
      <div className="mb-8">
        <h2 className="text-xl font-semibold mb-4">Доступные модули</h2>
        
        {loading ? (
          <div className="flex justify-center items-center p-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>
        ) : availableModules.length === 0 ? (
          <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
            <div className="flex items-center">
              <AlertCircle size={20} className="mr-2" />
              <span>Нет доступных модулей для установки</span>
            </div>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {availableModules.map((module) => (
              <div key={module.id} className="border rounded-lg overflow-hidden shadow-sm hover:shadow">
                <div className="p-4">
                  <div className="flex items-start justify-between">
                    <div>
                      <h3 className="text-lg font-semibold mb-1">{module.name}</h3>
                      <p className="text-xs text-gray-500 mb-2">Версия: {module.version}</p>
                    </div>
                    <Package size={20} className="text-gray-400" />
                  </div>
                  <p className="text-sm text-gray-600 mb-4">{module.description}</p>
                  
                  <button 
                    className="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded flex items-center justify-center"
                    onClick={() => installModule(module.id)}
                    disabled={actionInProgress === module.id}
                  >
                    {actionInProgress === module.id ? (
                      <>
                        <span className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                        Установка...
                      </>
                    ) : (
                      <>
                        <Download size={16} className="mr-2" />
                        Установить
                      </>
                    )}
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default Modules; 