/**
 * Switches question pages between two view modes:
 *  - 'all'    - one long scrolling page with all questions (default)
 *  - 'single' - one question at a time with previous/next buttons
 *
 * Question boxes are marked with 'js-paged-question' class. Chosen mode is remembered in localStorage.
 */
var QuestionViewMode = {

    _storageKey: 'questionViewMode',
    _currentIndex: 0,

    getMode: function() {
        let mode = null;
        try {
            mode = localStorage.getItem(this._storageKey);
        } catch (e) {
            // storage not available, use default
        }
        return mode === 'single' ? 'single' : 'all';
    },

    setMode: function(mode) {
        try {
            localStorage.setItem(this._storageKey, mode);
        } catch (e) {
            // storage not available, mode will not be remembered
        }
        this._applyMode(mode);
    },

    isSingleMode: function() {
        return document.documentElement.dataset.questionView === 'single';
    },

    /**
     * Make sure given question box is visible (switch to it in single mode) and scroll to it
     */
    reveal: function(questionElement) {
        if (this.isSingleMode()) {
            let index = this._getQuestions().indexOf(questionElement);
            if (index !== -1) {
                this.showQuestion(index);
            }
        }
        questionElement.scrollIntoView();
    },

    showQuestion: function(index) {
        let questions = this._getQuestions();
        if (questions.length === 0) {
            return;
        }

        index = Math.max(0, Math.min(index, questions.length - 1));
        let previousIndex = this._currentIndex;
        this._currentIndex = index;

        questions.forEach((questionElement, i) => {
            questionElement.classList.toggle('is-current-question', i === index);
        });

        if (this.isSingleMode() && index !== previousIndex) {
            this._animateQuestionIn(questions[index], index > previousIndex ? 1 : -1);
        }

        // in test, show only the heading of the part the current question belongs to
        let currentPart = questions[index].dataset.testPart;
        document.querySelectorAll('.js-paged-question-part-heading').forEach(headingElement => {
            headingElement.classList.toggle('is-current-question', headingElement.dataset.testPart === currentPart);
        });

        document.querySelectorAll('.js-question-pager').forEach(pagerElement => {
            pagerElement.querySelector('.js-question-pager-position').textContent = `${index + 1} / ${questions.length}`;
            pagerElement.querySelector('.js-question-pager-prev').disabled = index === 0;
            pagerElement.querySelector('.js-question-pager-next').disabled = index === questions.length - 1;
        });
    },

    /**
     * Re-read question boxes, e.g. after page content was replaced (resumed test)
     */
    refresh: function() {
        this.showQuestion(this._currentIndex);
    },

    /**
     * Slide question in from the side user is moving to (direction 1 = next, -1 = previous)
     */
    _animateQuestionIn: function(questionElement, direction) {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        questionElement.animate(
            {
                opacity: [0, 1],
                transform: [`translateX(${direction * 40}px)`, 'translateX(0)'],
            },
            { duration: 250, easing: 'ease-out' }
        );
    },

    /**
     * Popup where user types number of question to go to
     */
    _openGoToQuestionModal: function() {
        let modalElement = document.getElementById('goToQuestionModal');
        let inputElement = modalElement.querySelector('.js-go-to-question-input');
        let questionCount = this._getQuestions().length;

        inputElement.max = questionCount;
        inputElement.value = this._currentIndex + 1;
        modalElement.querySelector('.js-go-to-question-count').textContent = questionCount;
        inputElement.classList.remove('is-invalid');

        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    },

    _submitGoToQuestionModal: function() {
        let modalElement = document.getElementById('goToQuestionModal');
        let inputElement = modalElement.querySelector('.js-go-to-question-input');
        let questionNumber = Number(inputElement.value);

        if (!Number.isInteger(questionNumber) || questionNumber < 1 || questionNumber > this._getQuestions().length) {
            inputElement.classList.add('is-invalid');
            return;
        }

        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
        this.showQuestion(questionNumber - 1);
    },

    _getQuestions: function() {
        return [...document.querySelectorAll('.js-paged-question')];
    },

    _applyMode: function(mode) {
        let wasSingleMode = this.isSingleMode();
        let questions = this._getQuestions();

        if (mode === 'single' && !wasSingleMode && questions.length > 0) {
            // start at the question user is currently looking at
            this._currentIndex = this._getIndexOfQuestionInViewport(questions);
        }

        document.documentElement.dataset.questionView = mode;
        this.showQuestion(this._currentIndex);
        this._showActiveMode(mode);

        if (mode === 'all' && wasSingleMode && questions.length > 0) {
            // keep user's place in the long list
            questions[this._currentIndex].scrollIntoView();
        }
    },

    _getIndexOfQuestionInViewport: function(questions) {
        for (let i = 0; i < questions.length; i++) {
            if (questions[i].getBoundingClientRect().bottom > 0) {
                return i;
            }
        }
        return 0;
    },

    _showActiveMode: function(mode) {
        let activeButton = document.querySelector(`[data-question-view-value="${mode}"]`);
        if (!activeButton) {
            return;
        }

        document.querySelectorAll('[data-question-view-value]').forEach(element => {
            element.classList.remove('active');
            element.setAttribute('aria-pressed', 'false');
        });
        activeButton.classList.add('active');
        activeButton.setAttribute('aria-pressed', 'true');

        let activeIcon = document.querySelector('.question-view-icon-active use');
        activeIcon.setAttribute('href', activeButton.querySelector('svg use').getAttribute('href'));
    },

};

// set mode as early as possible to avoid showing all questions for a moment
document.documentElement.dataset.questionView = QuestionViewMode.getMode();

document.addEventListener("DOMContentLoaded", function() {

    QuestionViewMode.showQuestion(0);
    QuestionViewMode._showActiveMode(QuestionViewMode.getMode());

    document.querySelectorAll('[data-question-view-value]').forEach(toggle => {
        toggle.addEventListener('click', () => {
            QuestionViewMode.setMode(toggle.dataset.questionViewValue);
        });
    });

    // pager can be re-rendered (resumed test), so listen on document
    document.addEventListener('click', (e) => {
        if (e.target.closest('.js-question-pager-prev')) {
            QuestionViewMode.showQuestion(QuestionViewMode._currentIndex - 1);
        } else if (e.target.closest('.js-question-pager-next')) {
            QuestionViewMode.showQuestion(QuestionViewMode._currentIndex + 1);
        } else if (e.target.closest('.js-question-pager-position')) {
            QuestionViewMode._openGoToQuestionModal();
        }
    });

    let goToQuestionModalElement = document.getElementById('goToQuestionModal');
    if (goToQuestionModalElement) {
        goToQuestionModalElement.querySelector('form').addEventListener('submit', (e) => {
            e.preventDefault();
            QuestionViewMode._submitGoToQuestionModal();
        });
        goToQuestionModalElement.addEventListener('shown.bs.modal', () => {
            goToQuestionModalElement.querySelector('.js-go-to-question-input').select();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (!QuestionViewMode.isSingleMode() || e.altKey || e.ctrlKey || e.metaKey) {
            return;
        }
        if (e.target instanceof Element && e.target.closest('input, textarea, select, [contenteditable]')) {
            return;
        }
        if (e.key === 'ArrowLeft') {
            QuestionViewMode.showQuestion(QuestionViewMode._currentIndex - 1);
        } else if (e.key === 'ArrowRight') {
            QuestionViewMode.showQuestion(QuestionViewMode._currentIndex + 1);
        }
    });

});
