document.addEventListener('DOMContentLoaded', function() {
    var plans = document.querySelectorAll('.alma-installment-plan');
    if (plans.length === 0) return;

    function togglePlans() {
        plans.forEach(function(plan) {
            var card = plan.closest('.card, .item, label');
            if (!card) return;
            var radio = card.querySelector('input[type=radio]');
            plan.style.display = (radio && radio.checked) ? '' : 'none';
        });
    }

    var radios = document.querySelectorAll('input[type=radio][name*="method"]');
    radios.forEach(function(radio) {
        radio.addEventListener('change', togglePlans);
    });

    togglePlans();
});
