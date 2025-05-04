import React, { useEffect } from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

interface ProtectedRouteProps {
  requiredRole?: string;
}

export default function ProtectedRoute({ requiredRole }: ProtectedRouteProps) {
  const { isAuthenticated, user, isLoading } = useAuth();

  useEffect(() => {
    console.log('ProtectedRoute rendered - Auth state:', {
      isAuthenticated,
      isLoading,
      user: user ? `${user.name} (${user.role})` : 'none'
    });
  }, [isAuthenticated, isLoading, user]);

  if (isLoading) {
    console.log('ProtectedRoute - Loading state, showing spinner');
    // Показываем заглушку загрузки
    return (
      <div className="flex items-center justify-center min-h-screen bg-background">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto"></div>
          <p className="mt-4 text-lg font-medium">Загрузка...</p>
        </div>
      </div>
    );
  }

  // Если пользователь не авторизован, перенаправляем на страницу входа
  if (!isAuthenticated) {
    console.log('ProtectedRoute - Not authenticated, redirecting to login');
    return <Navigate to="/login" replace />;
  }

  // Если требуется определенная роль и у пользователя её нет, показываем ошибку доступа
  if (requiredRole && user?.role !== requiredRole) {
    console.log(`ProtectedRoute - Required role "${requiredRole}" not met, showing access denied`);
    return (
      <div className="flex items-center justify-center min-h-screen bg-background">
        <div className="text-center">
          <h1 className="text-3xl font-bold text-destructive mb-4">Доступ запрещен</h1>
          <p className="text-muted-foreground mb-4">
            У вас нет необходимых прав для доступа к этой странице
          </p>
          <a href="/admin" className="text-primary hover:underline">
            Вернуться на главную
          </a>
        </div>
      </div>
    );
  }

  // Если пользователь авторизован и имеет необходимую роль, показываем содержимое
  console.log('ProtectedRoute - Access granted, rendering content');
  return <Outlet />;
} 