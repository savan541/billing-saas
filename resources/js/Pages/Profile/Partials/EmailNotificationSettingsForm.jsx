import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function EmailNotificationSettingsForm({ className = '' }) {
    const [isSaving, setIsSaving] = useState(false);
    
    const { data, setData, patch, processing, errors } = useForm({
        email_notifications_enabled: true,
        email_notification_preferences: {
            invoice_created: true,
            invoice_paid: true,
            payment_receipt: true,
            recurring_invoice_generated: true,
        },
    });

    const submit = (e) => {
        e.preventDefault();
        setIsSaving(true);
        
        patch(route('profile.email-settings.update'), {
            onSuccess: () => {
                setIsSaving(false);
            },
            onError: () => {
                setIsSaving(false);
            },
        });
    };

    const handleGlobalToggle = (enabled) => {
        setData('email_notifications_enabled', enabled);
        if (!enabled) {
            setData('email_notification_preferences', {
                invoice_created: false,
                invoice_paid: false,
                payment_receipt: false,
                recurring_invoice_generated: false,
            });
        }
    };

    const handlePreferenceToggle = (type, enabled) => {
        setData('email_notification_preferences', {
            ...data.email_notification_preferences,
            [type]: enabled,
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">Email Notification Settings</h2>
                <p className="mt-1 text-sm text-gray-600">
                    Configure which email notifications you want to receive.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <div className="flex items-center">
                        <input
                            id="email_notifications_enabled"
                            type="checkbox"
                            className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            checked={data.email_notifications_enabled}
                            onChange={(e) => handleGlobalToggle(e.target.checked)}
                        />
                        <label htmlFor="email_notifications_enabled" className="ml-2 block text-sm font-medium text-gray-900">
                            Enable email notifications
                        </label>
                    </div>
                    {errors.email_notifications_enabled && (
                        <p className="mt-2 text-sm text-red-600">{errors.email_notifications_enabled}</p>
                    )}
                </div>

                {data.email_notifications_enabled && (
                    <div className="space-y-4 pl-6 border-l-2 border-gray-200">
                        <h3 className="text-sm font-medium text-gray-900">Notification Types</h3>
                        
                        <div className="space-y-3">
                            <div className="flex items-center">
                                <input
                                    id="invoice_created"
                                    type="checkbox"
                                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    checked={data.email_notification_preferences.invoice_created}
                                    onChange={(e) => handlePreferenceToggle('invoice_created', e.target.checked)}
                                />
                                <label htmlFor="invoice_created" className="ml-2 block text-sm text-gray-700">
                                    Invoice created notifications
                                </label>
                            </div>

                            <div className="flex items-center">
                                <input
                                    id="invoice_paid"
                                    type="checkbox"
                                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    checked={data.email_notification_preferences.invoice_paid}
                                    onChange={(e) => handlePreferenceToggle('invoice_paid', e.target.checked)}
                                />
                                <label htmlFor="invoice_paid" className="ml-2 block text-sm text-gray-700">
                                    Invoice paid notifications
                                </label>
                            </div>

                            <div className="flex items-center">
                                <input
                                    id="payment_receipt"
                                    type="checkbox"
                                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    checked={data.email_notification_preferences.payment_receipt}
                                    onChange={(e) => handlePreferenceToggle('payment_receipt', e.target.checked)}
                                />
                                <label htmlFor="payment_receipt" className="ml-2 block text-sm text-gray-700">
                                    Payment receipt notifications
                                </label>
                            </div>

                            <div className="flex items-center">
                                <input
                                    id="recurring_invoice_generated"
                                    type="checkbox"
                                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    checked={data.email_notification_preferences.recurring_invoice_generated}
                                    onChange={(e) => handlePreferenceToggle('recurring_invoice_generated', e.target.checked)}
                                />
                                <label htmlFor="recurring_invoice_generated" className="ml-2 block text-sm text-gray-700">
                                    Recurring invoice generated notifications
                                </label>
                            </div>
                        </div>
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <button
                        type="submit"
                        disabled={processing || isSaving}
                        className="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {isSaving ? 'Saving...' : 'Save Settings'}
                    </button>
                </div>
            </form>
        </section>
    );
}
