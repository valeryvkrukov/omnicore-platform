import { defineStore } from 'pinia';

export const useTenantStore = defineStore('tenant', {
    state: () => ({
        // Default tenant UUID for testing purposes
        currentTenantId: '018f9b2d-3c4a-7123-bc45-6789abcdef01',
        tenantName: 'Acme Marketing Corp',
        availableTenants: [
            { id: '018f9b2d-3c4a-7123-bc45-6789abcdef01', name: 'Acme Marketing Corp' },
            { id: '01901a2f-5b6c-7234-ad56-1234567890ab', name: 'Stripe SaaS Integration' }
        ]
    }),
    actions: {
        selectTenant(id) {
            const tenant = this.availableTenants.find(t => t.id === id);
            if (tenant) {
                this.currentTenantId = id;
                this.tenantName = tenant.name;
                // Here will be the trigger for data reloading on pages
            }
        }
    },
    getters: {
        apiHeaders: (state) => ({
            'X-Tenant-ID': state.currentTenantId,
            'Content-Type': 'application/json'
        })
    }
});