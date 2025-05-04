import React, { useState, useEffect } from 'react';
import { Link, Outlet, useNavigate } from 'react-router-dom';
import { cn } from '../lib/utils';
import { useAuth } from '../contexts/AuthContext';
import { contentTypesAPI, modulesAPI } from '../services/api';

// Иконки
import { 
  ChevronDown, 
  Home, 
  Users, 
  Settings,
  FileText,
  Layers,
  ShoppingCart,
  BarChart,
  Menu,
  X,
  LogOut,
  Image,
  User,
  Bell,
  Palette,
  Package
} from 'lucide-react';

// Интерфейс для типа контента
interface ContentType {
  id: number;
  name: string;
  label: string;
  slug: string;
}

interface SidebarItemProps {
  icon: React.ReactNode;
  title: string;
  path?: string;
  children?: { title: string; path: string }[];
}

const SidebarItem: React.FC<SidebarItemProps> = ({ icon, title, path, children }) => {
  const [open, setOpen] = useState(false);
  
  if (children) {
    return (
      <div className={cn("mb-2", open && "bg-secondary/50 rounded-md")}>
        <button
          onClick={() => setOpen(!open)}
          className="flex items-center w-full px-3 py-2 text-sm transition-colors rounded-md hover:bg-secondary group"
        >
          <span className="mr-2">{icon}</span>
          <span className="flex-1 text-left">{title}</span>
          <ChevronDown
            className={cn(
              "h-4 w-4 transition-transform",
              open && "transform rotate-180"
            )}
          />
        </button>
        {open && (
          <div className="pl-6 mt-1 space-y-1">
            {children.map((child, index) => (
              <Link
                key={index}
                to={child.path}
                className="flex items-center px-3 py-2 text-sm transition-colors rounded-md hover:bg-secondary"
              >
                {child.title}
              </Link>
            ))}
          </div>
        )}
      </div>
    );
  }

  return (
    <Link
      to={path || "#"}
      className="flex items-center px-3 py-2 text-sm transition-colors rounded-md hover:bg-secondary group mb-2"
    >
      <span className="mr-2">{icon}</span>
      <span>{title}</span>
    </Link>
  );
};

export default function DashboardLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  
  // Состояние для хранения типов контента
  const [contentTypes, setContentTypes] = useState<ContentType[]>([]);
  // Добавляем состояние для активных модулей
  const [activeModules, setActiveModules] = useState<{[key: string]: boolean}>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Загрузка типов контента и статуса модулей при монтировании компонента
  useEffect(() => {
    const fetchData = async () => {
      try {
        // Загружаем типы контента
        const contentResponse = await contentTypesAPI.getAll();
        setContentTypes(contentResponse.data.data || []);
        
        // Загружаем статус модулей
        console.log('Загружаем статус модулей...');
        const modulesResponse = await modulesAPI.getModulesStatus();
        console.log('Получен ответ от modulesAPI.getModulesStatus():', modulesResponse);
        
        if (modulesResponse.data && modulesResponse.data.modules) {
          console.log('Модули из ответа:', modulesResponse.data.modules);
          const moduleStatus: {[key: string]: boolean} = {};
          modulesResponse.data.modules.forEach((module: any) => {
            console.log(`Обработка модуля: ${module.slug}, статус: ${module.status}`);
            moduleStatus[module.slug] = module.status === 'active';
          });
          console.log('Итоговый статус модулей:', moduleStatus);
          setActiveModules(moduleStatus);
        } else {
          console.warn('Неверный формат ответа от API:', modulesResponse.data);
        }
        
        setLoading(false);
      } catch (err) {
        console.error('Ошибка при загрузке данных:', err);
        setError('Не удалось загрузить данные');
        setLoading(false);
      }
    };
    
    fetchData();
  }, []);
  
  // Проверим, что пользователь существует при загрузке компонента
  useEffect(() => {
    console.log('DashboardLayout - Current user:', user);
  }, [user]);
  
  // Подготавливаем дочерние элементы для меню "Контент"
  const contentMenuItems = [
    { title: "Страницы", path: "/admin/pages" },
    { title: "Блог", path: "/admin/posts" },
    // Добавляем типы контента из API
    ...contentTypes.map(type => ({
      title: type.label,
      path: `/admin/content-types/${type.id}/content`
    }))
  ];
  
  const handleLogout = () => {
    console.log('Logout requested');
    logout();
    navigate('/login');
  };

  // Проверяем активность модуля по его slug
  const isModuleActive = (moduleSlug: string) => {
    return activeModules[moduleSlug] === true;
  };

  return (
    <div className="flex h-screen bg-background">
      {/* Sidebar для мобильных устройств */}
      <div className={cn(
        "fixed inset-0 z-50 bg-background/80 backdrop-blur-sm lg:hidden",
        sidebarOpen ? "block" : "hidden"
      )}>
        <div className="fixed inset-y-0 left-0 w-full max-w-xs p-4 bg-background shadow-lg">
          <div className="flex items-center justify-between mb-8">
            <Link to="/admin" className="flex items-center">
              <span className="text-2xl font-bold">Universal CMS</span>
            </Link>
            <button
              onClick={() => setSidebarOpen(false)}
              className="p-2 rounded-md hover:bg-secondary"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
          <nav className="space-y-1">
            <SidebarItem icon={<Home className="w-5 h-5" />} title="Дашборд" path="/admin" />
            <SidebarItem 
              icon={<Layers className="w-5 h-5" />} 
              title="Контент" 
              children={contentMenuItems}
            />
            <SidebarItem icon={<FileText className="w-5 h-5" />} title="Типы контента" path="/admin/content-types" />
            <SidebarItem icon={<Users className="w-5 h-5" />} title="Пользователи" path="/admin/users" />
            
            {/* Отображаем пункт меню Shop только если модуль активен */}
            {isModuleActive('Shop') && (
              <SidebarItem 
                icon={<ShoppingCart className="w-5 h-5" />} 
                title="Магазин" 
                path="/admin/shop"
                children={[
                  { title: "Товары", path: "/admin/shop/products" },
                  { title: "Категории", path: "/admin/shop/categories" },
                  { title: "Заказы", path: "/admin/shop/orders" },
                  { title: "Настройки магазина", path: "/admin/shop/settings" }
                ]}
              />
            )}
            
            <SidebarItem icon={<BarChart className="w-5 h-5" />} title="CRM" path="/admin/crm" />
            <SidebarItem icon={<Image className="w-5 h-5" />} title="Медиа" path="/admin/media" />
            <SidebarItem icon={<FileText className="w-5 h-5" />} title="Меню" path="/admin/menus" />
            <SidebarItem icon={<Palette className="w-5 h-5" />} title="Темы" path="/admin/themes" />
            <SidebarItem icon={<Package className="w-5 h-5" />} title="Модули" path="/admin/modules" />
            <SidebarItem icon={<Settings className="w-5 h-5" />} title="Настройки" path="/admin/settings" />
          </nav>
        </div>
      </div>

      {/* Sidebar для десктопа */}
      <div className="hidden w-64 p-4 bg-background border-r lg:block">
        <div className="flex items-center mb-8">
          <Link to="/admin" className="flex items-center">
            <span className="text-2xl font-bold">Universal CMS</span>
          </Link>
        </div>
        <nav className="space-y-1">
          <SidebarItem icon={<Home className="w-5 h-5" />} title="Дашборд" path="/admin" />
          <SidebarItem 
            icon={<Layers className="w-5 h-5" />} 
            title="Контент" 
            children={contentMenuItems}
          />
          <SidebarItem icon={<FileText className="w-5 h-5" />} title="Типы контента" path="/admin/content-types" />
          <SidebarItem icon={<Users className="w-5 h-5" />} title="Пользователи" path="/admin/users" />
          
          {/* Отображаем пункт меню Shop только если модуль активен */}
          {isModuleActive('Shop') && (
            <SidebarItem 
              icon={<ShoppingCart className="w-5 h-5" />} 
              title="Магазин" 
              path="/admin/shop"
              children={[
                { title: "Товары", path: "/admin/shop/products" },
                { title: "Категории", path: "/admin/shop/categories" },
                { title: "Заказы", path: "/admin/shop/orders" },
                { title: "Настройки магазина", path: "/admin/shop/settings" }
              ]}
            />
          )}
          
          <SidebarItem icon={<BarChart className="w-5 h-5" />} title="CRM" path="/admin/crm" />
          <SidebarItem icon={<Image className="w-5 h-5" />} title="Медиа" path="/admin/media" />
          <SidebarItem icon={<FileText className="w-5 h-5" />} title="Меню" path="/admin/menus" />
          <SidebarItem icon={<Palette className="w-5 h-5" />} title="Темы" path="/admin/themes" />
          <SidebarItem icon={<Package className="w-5 h-5" />} title="Модули" path="/admin/modules" />
          <SidebarItem icon={<Settings className="w-5 h-5" />} title="Настройки" path="/admin/settings" />
        </nav>
      </div>

      {/* Основной контент */}
      <div className="flex flex-col flex-1 overflow-hidden">
        {/* Header */}
        <header className="flex items-center justify-between px-4 py-3 border-b">
          <div className="flex items-center lg:hidden">
            <button
              onClick={() => setSidebarOpen(true)}
              className="p-2 rounded-md hover:bg-secondary"
            >
              <Menu className="w-5 h-5" />
            </button>
          </div>
          <div className="flex items-center ml-auto space-x-4">
            <button className="p-2 rounded-md hover:bg-secondary">
              <Bell className="w-5 h-5" />
            </button>
            <div className="relative">
              <div className="flex items-center p-2 rounded-md hover:bg-secondary">
                <User className="w-5 h-5 mr-2" />
                <span className="mr-1">{user?.name || 'Пользователь'}</span>
                <button 
                  onClick={handleLogout}
                  className="ml-2 p-1 text-red-500 hover:bg-red-100 rounded"
                >
                  <LogOut className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </header>

        {/* Основной контент страницы */}
        <main className="flex-1 p-6 overflow-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
} 