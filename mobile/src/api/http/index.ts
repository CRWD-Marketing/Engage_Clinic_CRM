/**
 * The real implementation of ApiClient: each method is one call to the
 * Laravel mobile API (routes/api.php + routes/api/modules.php, served under
 * `/api/v1`). The server returns the shapes in ../types.ts, so there is no
 * mapping here beyond choosing the URL and the request body.
 */

import type { ApiClient } from '../client';
import { createHttp } from './request';

export function createHttpApi(baseUrl: string): ApiClient {
  const http = createHttp(baseUrl);
  // One endpoint for every role: the server answers with that role's dashboard.
  const dashboard = <T>() => http.get<T>('dashboard');

  return {
    auth: {
      login: ({ email, password }) => http.post('login', { email, password, device_name: 'mobile' }),
      me: () => http.get('me'),
      logout: async () => {
        await http.post('logout');
      },
      forgotPassword: (email) => http.post('forgot-password', { email }),
    },
    profile: {
      update: (input) => http.put('profile', input),
    },
    dashboard: {
      therapist: dashboard,
      supervisor: dashboard,
      coordinator: dashboard,
      admin: dashboard,
      sales: dashboard,
      hr: dashboard,
      finance: dashboard,
      otherStaff: dashboard,
    },
    notifications: {
      list: () => http.get('notifications'),
      read: (id) => http.post(`notifications/${encodeURIComponent(id)}/read`),
      readAll: () => http.post('notifications/read-all'),
    },
    patients: {
      createOptions: () => http.get('patients/create-options'),
      store: (input) => http.post('patients', input),
      list: (search) => http.get('patients', { search }),
      show: (id) => http.get(`patients/${id}`),
      addNote: (id, body) => http.post(`patients/${id}/notes`, { body }),
      saveTodayGoals: (id, input) => http.post(`patients/${id}/goals/today`, input),
      update: (id, input) => http.put(`patients/${id}`, input),
      addDocument: (id, input) => http.post(`patients/${id}/documents`, input),
      deleteDocument: (id, documentId) => http.delete(`patients/${id}/documents/${documentId}`),
    },
    notes: {
      review: (filter) => http.get('patient-notes/review', { filter }),
      signOff: (noteIds) => http.post('patient-notes/sign-off', { note_ids: noteIds }),
      flag: (id, reason) => http.post(`patient-notes/${id}/flag`, { flag_reason: reason }),
      unflag: (id) => http.delete(`patient-notes/${id}/flag`),
    },
    contacts: {
      list: () => http.get('contacts'),
      newCount: () => http.get('contacts/count'),
      updateSlot: (id, input) => http.patch(`contacts/${id}`, input),
      updateStatus: (id, status) => http.patch(`contacts/${id}/status`, { status }),
      sendEmail: (id, input) => http.post(`contacts/${id}/send-email`, input),
      convertToLead: (id) => http.post(`contacts/${id}/convert-to-lead`),
      destroy: (id) => http.delete(`contacts/${id}`),
    },
    leads: {
      board: () => http.get('leads'),
      show: (id) => http.get(`leads/${id}`),
      store: (input) => http.post('leads', input),
      update: (id, input) => http.put(`leads/${id}`, input),
      updateStatus: (id, status) => http.patch(`leads/${id}/status`, { status }),
      addNote: (id, body) => http.post(`leads/${id}/notes`, { body }),
      restore: (id) => http.post(`leads/${id}/restore`),
      convertToPatient: (id) => http.post(`leads/${id}/convert-to-patient`),
    },
    inbox: {
      list: () => http.get('inbox'),
      thread: (contactId) => http.get(`inbox/${contactId}`),
      poll: (contactId, after) => http.get('inbox/poll', { contact: contactId, after }),
      send: (contactId, message) => http.post('inbox/send', { contact_id: contactId, message }),
      setAiState: (contactId, state) => http.post(`inbox/${contactId}/ai-state`, { ai_state: state }),
      convertToLead: (contactId) => http.post(`inbox/${contactId}/convert-to-lead`),
    },
    calendar: {
      myWeek: (date) => http.get('calendar/my-week', { date }),
      feed: (start, end) => http.get('calendar/feed', { start, end }),
      show: (id) => http.get(`calendar/${id}`),
      saveTherapistNote: (id, note) => http.patch(`calendar/${id}/therapist-note`, { note }),
      supervise: (id, notes) => http.post(`calendar/${id}/supervision`, { notes }),
      unsupervise: (id) => http.delete(`calendar/${id}/supervision`),
      setStatus: (id, status) => http.put(`calendar/${id}`, { status }),
      bookingOptions: () => http.get('calendar/booking-options'),
      store: (input) => http.post('calendar', input),
      update: (id, input) => http.put(`calendar/${id}`, input),
      leaveImpact: (userId, date) => http.get('calendar/leave/impact', { user_id: userId, date }),
      markLeave: (input) => http.post('calendar/leave', input),
      removeLeave: (id) => http.delete(`calendar/leave/${id}`),
    },
    therapists: {
      index: (params) => http.get('therapists', params),
    },
    billing: {
      overview: () => http.get('billing'),
      invoice: (id) => http.get(`billing/invoices/${id}`),
      recordPayment: (id, input) => http.post(`billing/invoices/${id}/payments`, input),
      creditNote: (id, input) => http.post(`billing/invoices/${id}/credit`, input),
      voidInvoice: (id, input) => http.post(`billing/invoices/${id}/void`, input),
      sendEmail: (id, input) => http.post(`billing/invoices/${id}/send`, input),
      ledger: (patientId) => http.get(`billing/patients/${patientId}/ledger`),
      previewInvoice: (input) => http.post('billing/invoices/preview', input),
      createInvoice: (input) => http.post('billing/invoices', input),
      topUpPrepaid: (patientId, hours) => http.post(`billing/patients/${patientId}/top-up`, { hours }),
      statement: (patientId) => http.get(`billing/patients/${patientId}/statement`),
      updateClaim: (id, input) => http.patch(`billing/claims/${id}`, input),
      requestPreAuth: (input) => http.post('billing/pre-authorizations', input),
      bulkPreview: (query) => http.get('billing/bulk-run', { ...query }),
      bulkIssue: (input) => http.post('billing/bulk-run', input),
      invoicePdf: async (id) => http.file(`billing/invoices/${id}/pdf`, `invoice-${id}.pdf`),
      statementPdf: async (patientId) => http.file(`billing/patients/${patientId}/statement/pdf`, `statement-${patientId}.pdf`),
    },
    reports: {
      index: () => http.get('reports'),
    },
  };
}
