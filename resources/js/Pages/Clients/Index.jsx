import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ClientsIndex({ clients, currencyOptions }) {
    const { flash } = usePage().props;
    const [editingClient, setEditingClient] = useState(null);
    const [showCreateForm, setShowCreateForm] = useState(false);

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        company: '',
        address: '',
        tax_id: '',
        tax_country: '',
        tax_state: '',
        tax_rate: '',
        tax_exempt: false,
        tax_exemption_reason: '',
        currency: 'USD',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (editingClient) {
            put(route('clients.update', editingClient.id), {
                onSuccess: () => {
                    reset();
                    setEditingClient(null);
                },
            });
        } else {
            post(route('clients.store'), {
                onSuccess: () => {
                    reset();
                    setShowCreateForm(false);
                },
            });
        }
    };

    const handleEdit = (client) => {
        setEditingClient(client);
        setData({
            name: client.name,
            email: client.email || '',
            phone: client.phone || '',
            company: client.company || '',
            address: client.address || '',
            tax_id: client.tax_id || '',
            tax_country: client.tax_country || '',
            tax_state: client.tax_state || '',
            tax_rate: client.tax_rate || '',
            tax_exempt: client.tax_exempt || false,
            tax_exemption_reason: client.tax_exemption_reason || '',
            currency: client.currency || 'USD',
        });
        setShowCreateForm(false);
    };

    const handleDelete = (client) => {
        if (confirm('Are you sure you want to delete this client?')) {
            destroy(route('clients.destroy', client.id));
        }
    };

    const cancelEdit = () => {
        setEditingClient(null);
        reset();
    };

    const cancelCreate = () => {
        setShowCreateForm(false);
        reset();
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Clients
                </h2>
            }
        >
            <Head title="Clients" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {flash?.success && (
                                <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                    {flash.success}
                                </div>
                            )}

                            {(showCreateForm || editingClient) && (
                                <div className="mb-8 p-6 bg-gray-50 rounded-lg">
                                    <h3 className="text-lg font-medium mb-4">
                                        {editingClient ? 'Edit Client' : 'Create New Client'}
                                    </h3>
                                    <form onSubmit={handleSubmit}>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Name *
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.name}
                                                    onChange={(e) => setData('name', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    required
                                                />
                                                {errors.name && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Email
                                                </label>
                                                <input
                                                    type="email"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                />
                                                {errors.email && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Phone
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.phone}
                                                    onChange={(e) => setData('phone', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                />
                                                {errors.phone && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Company
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.company}
                                                    onChange={(e) => setData('company', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                />
                                                {errors.company && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.company}</p>
                                                )}
                                            </div>

                                            <div className="md:col-span-2">
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Address
                                                </label>
                                                <textarea
                                                    value={data.address}
                                                    onChange={(e) => setData('address', e.target.value)}
                                                    rows={3}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                />
                                                {errors.address && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                                                )}
                                            </div>

                                            <h4 className="md:col-span-2 text-lg font-medium mt-6 mb-4 text-gray-800 border-t pt-4">
                                                Tax Information
                                            </h4>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Currency
                                                </label>
                                                <select
                                                    value={data.currency}
                                                    onChange={(e) => setData('currency', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                >
                                                    {currencyOptions.map(option => (
                                                        <option key={option.value} value={option.value}>
                                                            {option.label}
                                                        </option>
                                                    ))}
                                                </select>
                                                {errors.currency && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.currency}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Tax ID (VAT/GST)
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.tax_id}
                                                    onChange={(e) => setData('tax_id', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="e.g., VAT123456789"
                                                />
                                                {errors.tax_id && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_id}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Country
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.tax_country}
                                                    onChange={(e) => setData('tax_country', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="US, CA, UK, etc."
                                                    maxLength={2}
                                                />
                                                {errors.tax_country && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_country}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    State/Province
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.tax_state}
                                                    onChange={(e) => setData('tax_state', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="CA, NY, ON, etc."
                                                    maxLength={50}
                                                />
                                                {errors.tax_state && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_state}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Tax Rate (%)
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    value={data.tax_rate ? (parseFloat(data.tax_rate) * 100) : ''}
                                                    onChange={(e) => setData('tax_rate', e.target.value ? parseFloat(e.target.value) / 100 : '')}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="8.25 (for 8.25%)"
                                                />
                                                <p className="mt-1 text-xs text-gray-500">Enter percentage (e.g., 8.25 for 8.25%)</p>
                                                {errors.tax_rate && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_rate}</p>
                                                )}
                                            </div>

                                            <div className="md:col-span-2">
                                                <label className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.tax_exempt}
                                                        onChange={(e) => setData('tax_exempt', e.target.checked)}
                                                        className="mr-2"
                                                    />
                                                    <span className="text-sm font-medium text-gray-700">
                                                        Tax Exempt
                                                    </span>
                                                </label>
                                                {errors.tax_exempt && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.tax_exempt}</p>
                                                )}
                                            </div>

                                            {data.tax_exempt && (
                                                <div className="md:col-span-2">
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Exemption Reason
                                                    </label>
                                                    <textarea
                                                        value={data.tax_exemption_reason}
                                                        onChange={(e) => setData('tax_exemption_reason', e.target.value)}
                                                        rows={2}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                        placeholder="Reason for tax exemption (e.g., Resale, Charity, Government)"
                                                    />
                                                    {errors.tax_exemption_reason && (
                                                        <p className="mt-1 text-sm text-red-600">{errors.tax_exemption_reason}</p>
                                                    )}
                                                </div>
                                            )}
                                        </div>

                                        <div className="mt-4 flex gap-2">
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {processing ? 'Saving...' : (editingClient ? 'Update Client' : 'Create Client')}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={editingClient ? cancelEdit : cancelCreate}
                                                className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            )}

                            {!showCreateForm && !editingClient && (
                                <button
                                    onClick={() => setShowCreateForm(true)}
                                    className="mb-6 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    Add New Client
                                </button>
                            )}

                            {clients.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Email
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Phone
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Company
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Address
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {clients.map((client) => (
                                                <tr key={client.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {client.name}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {client.email || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {client.phone || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {client.company || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-500">
                                                        {client.address || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <button
                                                            onClick={() => handleEdit(client)}
                                                            className="text-blue-600 hover:text-blue-900 mr-3"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            onClick={() => handleDelete(client)}
                                                            className="text-red-600 hover:text-red-900"
                                                        >
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    No clients found. Create your first client to get started.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
