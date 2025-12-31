import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function RecordPaymentModal({ invoice, show, onClose }) {
    const [data, setData] = useState({
        amount: '',
        payment_method: 'cash',
        payment_date: new Date().toISOString().split('T')[0],
        notes: '',
    });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const paymentMethods = [
        { value: 'cash', label: 'Cash' },
        { value: 'bank_transfer', label: 'Bank Transfer' },
        { value: 'upi', label: 'UPI' },
        { value: 'card', label: 'Card' },
    ];

    const handleSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        router.post(
            route('invoices.payments.store', invoice.id),
            data,
            {
                onSuccess: (page) => {
                    setData({
                        amount: '',
                        payment_method: 'cash',
                        payment_date: new Date().toISOString().split('T')[0],
                        notes: '',
                    });
                    onClose();
                },
                onError: (errors) => {
                    setErrors(errors);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            }
        );
    };

    const maxAmount = invoice.total - (invoice.total_paid || 0);

    return (
        <Modal show={show} onClose={onClose}>
            <form onSubmit={handleSubmit} className="p-6">
                <h2 className="text-lg font-medium text-gray-900 mb-4">
                    Record Payment for Invoice {invoice.invoice_number}
                </h2>

                <div className="mb-4">
                    <div className="flex justify-between items-center mb-2">
                        <span className="text-sm text-gray-600">Invoice Total:</span>
                        <span className="font-medium">${parseFloat(invoice.total).toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between items-center mb-2">
                        <span className="text-sm text-gray-600">Already Paid:</span>
                        <span className="font-medium">${parseFloat(invoice.total_paid || 0).toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between items-center">
                        <span className="text-sm text-gray-600">Remaining Balance:</span>
                        <span className="font-medium text-green-600">${parseFloat(maxAmount).toFixed(2)}</span>
                    </div>
                </div>

                <div className="mb-4">
                    <InputLabel htmlFor="amount">Payment Amount *</InputLabel>
                    <TextInput
                        id="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        max={maxAmount}
                        value={data.amount}
                        onChange={(e) => setData({ ...data, amount: e.target.value })}
                        className="w-full"
                        required
                    />
                    <InputError message={errors.amount} className="mt-2" />
                </div>

                <div className="mb-4">
                    <InputLabel htmlFor="payment_method">Payment Method *</InputLabel>
                    <select
                        id="payment_method"
                        value={data.payment_method}
                        onChange={(e) => setData({ ...data, payment_method: e.target.value })}
                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required
                    >
                        {paymentMethods.map((method) => (
                            <option key={method.value} value={method.value}>
                                {method.label}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.payment_method} className="mt-2" />
                </div>

                <div className="mb-4">
                    <InputLabel htmlFor="payment_date">Payment Date *</InputLabel>
                    <TextInput
                        id="payment_date"
                        type="date"
                        value={data.payment_date}
                        onChange={(e) => setData({ ...data, payment_date: e.target.value })}
                        className="w-full"
                        max={new Date().toISOString().split('T')[0]}
                        required
                    />
                    <InputError message={errors.payment_date} className="mt-2" />
                </div>

                <div className="mb-4">
                    <InputLabel htmlFor="notes">Notes</InputLabel>
                    <textarea
                        id="notes"
                        value={data.notes}
                        onChange={(e) => setData({ ...data, notes: e.target.value })}
                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        rows={3}
                        placeholder="Optional notes about this payment..."
                    />
                    <InputError message={errors.notes} className="mt-2" />
                </div>

                <div className="flex justify-end gap-2">
                    <SecondaryButton type="button" onClick={onClose} disabled={processing}>
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" disabled={processing}>
                        {processing ? 'Recording...' : 'Record Payment'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
