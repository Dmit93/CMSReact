import React, { useState, useEffect } from 'react';
import { 
  Card, CardHeader, CardTitle, CardContent
} from '../../components/ui/card';
import { 
  Table, TableHeader, TableBody, TableRow, 
  TableHead, TableCell 
} from '../../components/ui/table';
import { Button } from '../../components/ui/button';
import apiClient, { themesAPI } from '../../services/api';
import { 
  Eye, ExternalLink, Settings, 
  Monitor, Loader2, Check, 
  AlertCircle
} from 'lucide-react';

interface Theme {
  name: string;
  title: string;
  description: string;
  version: string;
  author: string;
  active: boolean;
}

interface ThemeInfo extends Theme {
  screenshots: string[];
  templates: string[];
}

const ThemeManager: React.FC = () => {
  const [themes, setThemes] = useState<Theme[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [activating, setActivating] = useState<string | null>(null);
  const [selectedTheme, setSelectedTheme] = useState<ThemeInfo | null>(null);
  const [showModal, setShowModal] = useState<boolean>(false);

  // Загрузка списка тем
  useEffect(() => {
    loadThemes();
  }, []);

  const loadThemes = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await themesAPI.getAll();
      
      if (response && response.data && response.data.success) {
        setThemes(response.data.data);
      } else {
        setError((response?.data?.message) || 'Не удалось загрузить темы');
      }
    } catch (err) {
      setError('Ошибка при загрузке тем');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // Активация темы
  const activateTheme = async (themeName: string) => {
    try {
      setActivating(themeName);
      setError(null);
      
      const response = await themesAPI.activate(themeName);
      
      if (response && response.data && response.data.success) {
        // Обновляем статусы тем
        setThemes(prevThemes => 
          prevThemes.map(theme => ({
            ...theme,
            active: theme.name === themeName
          }))
        );
      } else {
        setError((response?.data?.message) || `Не удалось активировать тему ${themeName}`);
      }
    } catch (err) {
      setError(`Ошибка при активации темы ${themeName}`);
      console.error(err);
    } finally {
      setActivating(null);
    }
  };

  // Просмотр детальной информации о теме
  const viewThemeDetails = async (themeName: string) => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await themesAPI.getById(themeName);
      
      if (response && response.data && response.data.success) {
        setSelectedTheme(response.data.data);
        setShowModal(true);
      } else {
        setError((response?.data?.message) || `Не удалось загрузить информацию о теме ${themeName}`);
      }
    } catch (err) {
      setError(`Ошибка при загрузке информации о теме ${themeName}`);
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // Просмотр сайта
  const viewSite = () => {
    window.open('/frontend', '_blank');
  };

  // Просмотр сайта с конкретной темой
  const previewTheme = (themeName: string) => {
    window.open(`/preview/${themeName}`, '_blank');
  };

  // Закрытие модального окна
  const handleCloseModal = () => {
    setShowModal(false);
    setSelectedTheme(null);
  };

  // Переход к настройкам сайта
  const goToSettings = () => {
    window.location.href = '/admin/settings';
  };

  // Отображение бейджа статуса
  const getStatusBadge = (isActive: boolean) => {
    return (
      <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
        ${isActive 
          ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' 
          : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
        }`}
      >
        {isActive ? 'Активна' : 'Неактивна'}
      </span>
    );
  };

  return (
    <Card className="w-full">
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle>Управление темами</CardTitle>
        <div className="flex gap-2">
          <Button 
            variant="outline" 
            size="sm" 
            onClick={viewSite}
            className="flex items-center gap-2"
          >
            <ExternalLink className="h-4 w-4" />
            Перейти на сайт
          </Button>
          <Button 
            variant="outline" 
            size="sm" 
            onClick={goToSettings}
            className="flex items-center gap-2"
          >
            <Settings className="h-4 w-4" />
            Настройки сайта
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        {error && (
          <div className="bg-red-50 text-red-800 p-4 mb-4 rounded-md flex items-start">
            <AlertCircle className="h-5 w-5 mr-2 flex-shrink-0" />
            <p>{error}</p>
          </div>
        )}
        
        {loading && !themes.length ? (
          <div className="flex flex-col items-center justify-center py-8">
            <Loader2 className="h-10 w-10 text-muted-foreground animate-spin mb-4" />
            <p className="text-muted-foreground">Загрузка тем...</p>
          </div>
        ) : (
          <div className="border rounded-md">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Название</TableHead>
                  <TableHead>Описание</TableHead>
                  <TableHead>Версия</TableHead>
                  <TableHead>Автор</TableHead>
                  <TableHead>Статус</TableHead>
                  <TableHead>Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {themes.map(theme => (
                  <TableRow key={theme.name}>
                    <TableCell>{theme.title}</TableCell>
                    <TableCell>{theme.description}</TableCell>
                    <TableCell>{theme.version}</TableCell>
                    <TableCell>{theme.author}</TableCell>
                    <TableCell>{getStatusBadge(theme.active)}</TableCell>
                    <TableCell>
                      <div className="flex gap-2">
                        <Button 
                          variant="outline" 
                          size="sm" 
                          onClick={() => viewThemeDetails(theme.name)}
                        >
                          <Eye className="h-4 w-4 mr-1" />
                          <span className="sr-only md:not-sr-only md:inline">Детали</span>
                        </Button>
                        
                        <Button 
                          variant="outline" 
                          size="sm" 
                          onClick={() => previewTheme(theme.name)}
                        >
                          <Monitor className="h-4 w-4 mr-1" />
                          <span className="sr-only md:not-sr-only md:inline">Просмотр</span>
                        </Button>
                        
                        {!theme.active && (
                          <Button 
                            variant="default" 
                            size="sm" 
                            disabled={activating === theme.name}
                            onClick={() => activateTheme(theme.name)}
                            className="flex items-center"
                          >
                            {activating === theme.name ? (
                              <>
                                <Loader2 className="h-4 w-4 mr-1 animate-spin" />
                                <span>Активация...</span>
                              </>
                            ) : (
                              <>
                                <Check className="h-4 w-4 mr-1" />
                                <span>Активировать</span>
                              </>
                            )}
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
                
                {themes.length === 0 && !loading && (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                      Нет доступных тем
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>

      {/* Модальное окно с деталями (простая замена Dialog) */}
      {showModal && selectedTheme && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white dark:bg-gray-800 rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-bold">{selectedTheme.title}</h2>
              <button 
                onClick={handleCloseModal}
                className="text-gray-500 hover:text-gray-700"
              >
                ✕
              </button>
            </div>
            
            <div className="text-sm text-gray-500 mb-4">
              Подробная информация о теме
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
              <div>
                <h3 className="font-medium mb-2">Информация о теме</h3>
                <dl className="space-y-2">
                  <div>
                    <dt className="text-sm font-medium text-muted-foreground">Имя</dt>
                    <dd>{selectedTheme.name}</dd>
                  </div>
                  <div>
                    <dt className="text-sm font-medium text-muted-foreground">Описание</dt>
                    <dd>{selectedTheme.description}</dd>
                  </div>
                  <div>
                    <dt className="text-sm font-medium text-muted-foreground">Версия</dt>
                    <dd>{selectedTheme.version}</dd>
                  </div>
                  <div>
                    <dt className="text-sm font-medium text-muted-foreground">Автор</dt>
                    <dd>{selectedTheme.author}</dd>
                  </div>
                  <div>
                    <dt className="text-sm font-medium text-muted-foreground">Статус</dt>
                    <dd>{getStatusBadge(selectedTheme.active)}</dd>
                  </div>
                </dl>
              </div>
              
              <div>
                <h3 className="font-medium mb-2">Доступные шаблоны</h3>
                <div className="flex flex-wrap gap-2">
                  {selectedTheme.templates.map(template => (
                    <span 
                      key={template} 
                      className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                    >
                      {template}.php
                    </span>
                  ))}
                </div>
              </div>
            </div>
            
            {selectedTheme.screenshots && selectedTheme.screenshots.length > 0 && (
              <div className="py-4">
                <h3 className="font-medium mb-4">Скриншоты</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {selectedTheme.screenshots.map((screenshot, index) => (
                    <div key={index} className="overflow-hidden rounded-md border">
                      <img 
                        src={screenshot} 
                        alt={`Скриншот темы ${selectedTheme.title} ${index + 1}`}
                        className="w-full h-auto object-cover aspect-video"
                      />
                    </div>
                  ))}
                </div>
              </div>
            )}
            
            <div className="flex justify-between items-center mt-6 pt-4 border-t">
              <Button 
                variant="outline" 
                onClick={() => previewTheme(selectedTheme.name)}
                className="flex items-center gap-2"
              >
                <Monitor className="h-4 w-4" />
                Просмотреть тему
              </Button>
              
              {!selectedTheme.active && (
                <Button 
                  variant="default" 
                  disabled={activating === selectedTheme.name}
                  onClick={() => {
                    activateTheme(selectedTheme.name);
                    handleCloseModal();
                  }}
                  className="flex items-center gap-2"
                >
                  {activating === selectedTheme.name ? (
                    <>
                      <Loader2 className="h-4 w-4 animate-spin" />
                      Активация...
                    </>
                  ) : (
                    <>
                      <Check className="h-4 w-4" />
                      Активировать тему
                    </>
                  )}
                </Button>
              )}
            </div>
          </div>
        </div>
      )}
    </Card>
  );
};

export default ThemeManager; 