document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
                
                // Update URL without jumping
                if (history.pushState) {
                    history.pushState(null, null, targetId);
                } else {
                    location.hash = targetId;
                }
            }
        });
    });
    
    // Form submission
    const contactForm = document.getElementById('formContacto');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simulate form submission
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
            
            // Simulate API call
            setTimeout(() => {
                alert('¡Gracias por tu mensaje! Nos pondremos en contacto contigo pronto.');
                contactForm.reset();
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }, 1500);
        });
    }
    
    // Scroll animations
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.product-card, .testimonial-card, .contact-form');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Set initial state for animated elements
    document.querySelectorAll('.product-card, .testimonial-card, .contact-form').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
    });
    
    // Run once on load
    animateOnScroll();
    
    // Then on scroll
    window.addEventListener('scroll', animateOnScroll);
    
    // Navbar background change on scroll
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.style.background = 'var(--primary-color)';
                navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            } else {
                navbar.style.background = 'linear-gradient(135deg, var(--primary-color), var(--secondary-color))';
                navbar.style.boxShadow = 'none';
            }
        });
    }
});

function adjustTextContrast() {
    const sections = document.querySelectorAll('.section-container');
    
    sections.forEach(section => {
        const isLightSection = section.classList.contains('bg-light');
        
        if (isLightSection) {
            section.querySelectorAll('h2, h3, p').forEach(el => {
                el.style.color = 'var(--dark-color)';
                el.style.textShadow = 'none';
            });
        } else {
            section.querySelectorAll('h2, h3, p').forEach(el => {
                el.style.color = 'white';
                el.style.textShadow = '1px 1px 3px rgba(0,0,0,0.7)';
            });
        }
    });
}

window.addEventListener('scroll', adjustTextContrast);
window.addEventListener('resize', adjustTextContrast);
adjustTextContrast(); // Ejecutar al cargar