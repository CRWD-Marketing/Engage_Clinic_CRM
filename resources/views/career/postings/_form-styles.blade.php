<style>
    .jp-form-page {
        margin: -22px -28px;
        padding: 26px 32px 40px;
        background: #FFFDFA;
        min-height: 100%;
        box-sizing: border-box;
    }

    .jp-form-title { font: 600 26px 'Baloo 2'; color: #16436E; line-height: 1.2; }
    .jp-form-subtitle { font: 600 13px 'Nunito Sans'; color: #98897A; margin-top: 4px; }

    .jp-form-back {
        display: inline-flex; align-items: center; gap: 6px; color: #5A6B7E; font: 700 12.5px 'Nunito Sans';
        text-decoration: none; border: 1px solid #E2DACE; border-radius: 8px; padding: 8px 14px; background: #FFFFFF;
    }
    .jp-form-back:hover { background: #F6F3EE; }

    .jp-form-rule { height: 1px; background: #EBE4DA; margin: 20px 0 24px; }

    .jp-form-card {
        border: 1px solid #EBE4DA; border-radius: 16px; background: #FFFFFF;
        padding: 26px 28px; box-shadow: 0 2px 10px rgba(22,42,60,0.04);
    }

    .jp-form-section-title {
        font: 800 11px 'Nunito Sans'; text-transform: uppercase; letter-spacing: 0.7px; color: #98897A;
    }

    .jp-form-label { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 6px; display: block; }
    .jp-form-label .required { color: #C8355F; margin-left: 2px; }

    .jp-input, .jp-select, .jp-textarea {
        border: 1px solid #E2DACE; border-radius: 8px; background: #F6F3EE; width: 100%;
        font: 700 13.5px 'Nunito Sans'; color: #2B3A4C; box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .jp-input::placeholder, .jp-textarea::placeholder { color: #B0A493; font-weight: 600; }
    .jp-input:focus, .jp-select:focus, .jp-textarea:focus {
        outline: none; border-color: #C8355F; box-shadow: 0 0 0 3px rgba(200,53,95,0.12);
    }
    .jp-input.is-invalid, .jp-select.is-invalid, .jp-textarea.is-invalid { border-color: #C8355F; background: #FDF1F3; }

    .jp-field-error { color: #C8355F; font-size: 0.75rem; font-weight: 700; margin-top: 4px; }

    .jp-form-btn-primary {
        background: #C8355F; color: #fff; font: 800 13px 'Nunito Sans'; border-radius: 8px;
        box-shadow: 0 4px 14px rgba(200,53,95,0.28); transition: background-color 0.15s ease, transform 0.05s ease;
        border: none;
    }
    .jp-form-btn-primary:hover { background: #A82348; }
    .jp-form-btn-primary:active { transform: translateY(1px); }

    .jp-form-btn-ghost {
        color: #5A6B7E; border-radius: 8px; font: 700 13px 'Nunito Sans';
        transition: background-color 0.12s ease, color 0.12s ease; text-decoration: none;
    }
    .jp-form-btn-ghost:hover { background: #F6F3EE; color: #16436E; }

    .jp-form-alert {
        border-radius: 10px; padding: 12px 14px; font: 700 13px 'Nunito Sans'; display: none;
    }
    .jp-form-alert.is-visible { display: flex; }
    .jp-form-alert.is-error { background: #FDF1F3; border: 1px solid #F3C6D2; color: #A82348; }
</style>
