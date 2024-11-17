document.addEventListener("DOMContentLoaded", function() {

    document.querySelectorAll('.js-answer-row').forEach(answerRow => {
        answerRow.addEventListener('click', (e) => {
            e.preventDefault();
            isCorrectAnswer = answerRow.dataset.isCorrect === '1' ? true : false;
            setAnswerRowStatusForAnswerRow(answerRow, isCorrectAnswer);
        })
    });

    function setAnswerRowStatusForAnswerRow(answerRow, isCorrectAnswer) {
        answerRow.classList.remove("list-group-item-secondary");

        const bulletElement = answerRow.querySelector('.js-question-bullet');
        bulletElement.animate({ opacity: [0, 1] }, { duration: 1000, iterations: 1, easing: "ease-out" })
            .onfinish = (e) => {
                 e.target.effect.target.style.opacity = 1;
            };

        if (isCorrectAnswer) {
            answerRow.classList.add("list-group-item-success");
            bulletElement.textContent = '✅';
        } else {
            answerRow.classList.add("list-group-item-danger");
            bulletElement.textContent = '❌';
        }
    }

});