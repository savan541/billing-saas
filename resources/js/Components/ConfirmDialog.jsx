import React, { useState } from 'react';

export default function ConfirmDialog({ 
    isOpen, 
    onClose, 
    onConfirm, 
    title, 
    message, 
    confirmText = 'Confirm', 
    cancelText = 'Cancel',
    type = 'danger'
}) {
    const [isConfirming, setIsConfirming] = useState(false);

    const handleConfirm = async () => {
        setIsConfirming(true);
        try {
            await onConfirm();
            onClose();
        } finally {
            setIsConfirming(false);
        }
    };

    if (!isOpen) return null;

    const typeStyles = {
        danger: {
            bg: 'bg-red-50',
            border: 'border-red-200',
            title: 'text-red-900',
            button: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
            icon: 'text-red-600'
        },
        warning: {
            bg: 'bg-yellow-50',
            border: 'border-yellow-200',
            title: 'text-yellow-900',
            button: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
            icon: 'text-yellow-600'
        },
        info: {
            bg: 'bg-blue-50',
            border: 'border-blue-200',
            title: 'text-blue-900',
            button: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
            icon: 'text-blue-600'
        }
    };

    const styles = typeStyles[type] || typeStyles.danger;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    onClick={onClose}
                />
                
                <div className="inline-block transform overflow-hidden rounded-lg text-left align-bottom transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div className={`${styles.bg} px-4 pt-5 pb-4 sm:p-6 sm:pb-4`}>
                        <div className="sm:flex sm:items-start">
                            <div className={`mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10 ${styles.bg} ${styles.icon}`}>
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 className={`text-lg font-medium leading-6 ${styles.title}`}>
                                    {title}
                                </h3>
                                <div className="mt-2">
                                    <p className="text-sm text-gray-500">
                                        {message}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className={`${styles.bg} px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 sm:py-4`}>
                        <button
                            type="button"
                            className={`inline-flex w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm ${styles.button} disabled:opacity-50 disabled:cursor-not-allowed`}
                            onClick={handleConfirm}
                            disabled={isConfirming}
                        >
                            {isConfirming ? 'Processing...' : confirmText}
                        </button>
                        <button
                            type="button"
                            className="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            onClick={onClose}
                            disabled={isConfirming}
                        >
                            {cancelText}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
