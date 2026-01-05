import React from 'react';

export default function EmptyState({ 
    icon, 
    title, 
    description, 
    action, 
    actionText 
}) {
    return (
        <div className="text-center py-12">
            <div className="mx-auto h-12 w-12 text-gray-400 mb-4">
                {icon}
            </div>
            <h3 className="mt-2 text-sm font-medium text-gray-900">
                {title}
            </h3>
            <p className="mt-1 text-sm text-gray-500 mb-6">
                {description}
            </p>
            {action && (
                <div className="flex justify-center">
                    {action}
                </div>
            )}
        </div>
    );
}
