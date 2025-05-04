import { BrowserRouter as Router, Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import DashboardLayout from './layouts/DashboardLayout';
import ProtectedRoute from './components/ProtectedRoute';
import Dashboard from './pages/Dashboard';
import Users from './pages/Users';
import Login from './pages/Login';
import ContentTypes from './pages/ContentTypes';
import ContentTypeForm from './pages/ContentTypeForm';
import ContentTypeFields from './pages/ContentTypeFields';
import ContentTypeFieldForm from './pages/ContentTypeFieldForm';
import ContentList from './pages/ContentList';
import ContentForm from './pages/ContentForm';
import ThemeManager from './components/Themes/ThemeManager';
import Settings from './pages/Settings';
import Modules from './pages/Modules';
// Импортируем страницы модуля Shop
import ShopIndex from './pages/Shop/Index';
import ShopProducts from './pages/Shop/Products';
import ShopCategories from './pages/Shop/Categories';
import ShopOrders from './pages/Shop/Orders';
import ShopSettings from './pages/Shop/Settings';
import { useEffect } from 'react';

// Компонент для условного перенаправления
function ConditionalRedirect() {
  const location = useLocation();
  
  // Если URL уже начинается с /admin, ничего не делаем 
  if (location.pathname.startsWith('/admin')) {
    return null;
  }
  // Иначе перенаправляем на корневой URL
  return <Navigate to="/" replace />;
}

function App() {
  return (
    <Router>
      <AuthProvider>
        <Routes>
          {/* Публичные маршруты */}
          <Route path="/login" element={<Login />} />
          
          {/* Главная страница сайта */}
          <Route path="/" element={<FrontendSiteHandler />} />
          
          {/* Обработка маршрута /frontend для правильной работы навигации */}
          <Route path="/frontend" element={<FrontendSiteHandler />} />
          
          {/* Защищенные маршруты админ-панели */}
          <Route path="/admin" element={<ProtectedRoute />}>
            <Route element={<DashboardLayout />}>
              <Route index element={<Dashboard />} />
              <Route path="users" element={<Users />} />
              
              {/* Маршруты для типов контента */}
              <Route path="content-types" element={<ContentTypes />} />
              <Route path="content-types/create" element={<ContentTypeForm />} />
              <Route path="content-types/:id/edit" element={<ContentTypeForm />} />
              <Route path="content-types/:id/fields" element={<ContentTypeFields />} />
              <Route path="content-types/:contentTypeId/fields/create" element={<ContentTypeFieldForm />} />
              <Route path="content-types/:contentTypeId/fields/:fieldId/edit" element={<ContentTypeFieldForm />} />
              
              {/* Маршруты для записей контента */}
              <Route path="content-types/:typeId/content" element={<ContentList />} />
              <Route path="content-types/:typeId/content/new" element={<ContentForm />} />
              <Route path="content-types/:typeId/content/:id/edit" element={<ContentForm />} />
              
              <Route path="pages" element={<div className="p-6">Страницы (в разработке)</div>} />
              <Route path="posts" element={<div className="p-6">Блог (в разработке)</div>} />
              
              {/* Маршруты для магазина */}
              <Route path="shop" element={<ShopIndex />} />
              <Route path="shop/products" element={<ShopProducts />} />
              <Route path="shop/categories" element={<ShopCategories />} />
              <Route path="shop/orders" element={<ShopOrders />} />
              <Route path="shop/settings" element={<ShopSettings />} />
              
              <Route path="crm" element={<div className="p-6">CRM (в разработке)</div>} />
              <Route path="media" element={<div className="p-6">Медиа (в разработке)</div>} />
              <Route path="menus" element={<div className="p-6">Меню (в разработке)</div>} />
              <Route path="themes" element={<ThemeManager />} />
              <Route path="modules" element={<Modules />} />
              <Route path="settings" element={<Settings />} />
            </Route>
          </Route>
          
          {/* Перенаправляем все остальные маршруты на главную страницу сайта, кроме маршрутов для админки */}
          <Route path="*" element={<ConditionalRedirect />} />
        </Routes>
      </AuthProvider>
    </Router>
  );
}

// Компонент для обработки запросов к публичному сайту
function FrontendSiteHandler() {
  useEffect(() => {
    // Перенаправляем на статическую страницу без использования React Router
    window.location.href = '/frontend/index.html';
  }, []);
  
  // Возвращаем пустой компонент, так как произойдет перенаправление
  return <div>Перенаправление на публичный сайт...</div>;
}

export default App;
