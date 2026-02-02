<?php
require_once 'includes/data.php';
include 'includes/header.php';
?>

<div class="about-hero">
    <div class="container">
        <h1 class="page-title about-title">Nossa Essência</h1>
        <p class="about-subtitle">Transformando vidas através da arte desde 2013</p>
    </div>
</div>

<div class="container tabs-section">
    <div class="tabs-container">
        <div class="tabs">
            <button class="tab-link active" onclick="openTab(event, 'quem-somos')">
                <i class="fas fa-users"></i> Quem Somos
            </button>
            <button class="tab-link" onclick="openTab(event, 'transparencia')">
                <i class="fas fa-hand-holding-heart"></i> Transparência
            </button>
        </div>

        <!-- ABA QUEM SOMOS -->
        <div id="quem-somos" class="tab-content active">
            <div class="about-grid">
                <div class="about-text">
                    <h3 class="section-title">Nossa História</h3>
                    <p class="lead-text">
                        O Grupo Independance é uma iniciativa social independente e voluntária que transforma a realidade de crianças e jovens em Alvorada, Rio Grande do Sul.
                    </p>
                    <p>
                        Fundado por <strong>Clau Malta, Juli Malta e Marlene Costa</strong>, o projeto nasceu da união familiar e do desejo de oferecer arte, disciplina e esperança em territórios de vulnerabilidade social. O que começou como uma pequena oficina hoje é uma rede de apoio comunitário que atende cerca de <strong>160 integrantes</strong>.
                    </p>
                    <div class="quote-box">
                        <i class="fas fa-quote-left"></i>
                        <p>Oferecemos aulas gratuitas de Ballet Clássico, Jazz e Danças Contemporâneas para alunos e alunas que veem na dança uma possibilidade de cultivar novos sonhos.</p>
                    </div>
                </div>
                <div class="about-image-placeholder">
                    <i class="fas fa-star about-icon-bg"></i>
                </div>
            </div>

            <h3 class="section-title center-title">Nossa Filosofia</h3>
            <div class="philosophy-grid">
                <div class="phil-card">
                    <div class="phil-icon"><i class="fas fa-graduation-cap"></i></div>
                    <h4>Educação</h4>
                    <p>O balé e os estudos caminham juntos. O bom desempenho escolar é requisito obrigatório para permanecer no projeto.</p>
                </div>
                <div class="phil-card">
                    <div class="phil-icon"><i class="fas fa-clock"></i></div>
                    <h4>Disciplina</h4>
                    <p>Valorizamos a assiduidade e pontualidade, ensinando responsabilidade e compromisso desde cedo.</p>
                </div>
                <div class="phil-card">
                    <div class="phil-icon"><i class="fas fa-heart"></i></div>
                    <h4>Vínculos</h4>
                    <p>Funcionamos como uma grande família, onde o apoio mútuo ajuda a superar desafios sociais e pessoais.</p>
                </div>
            </div>
        </div>

        <!-- ABA TRANSPARÊNCIA -->
        <div id="transparencia" class="tab-content">
            <div class="transparency-header">
                <h3>Sustentabilidade e Compromisso</h3>
                <p>
                    O Grupo Independance é uma entidade formalizada (CNPJ 48.059.929/0001-12). <br>
                    <strong>Não visamos lucro:</strong> todos os recursos são reinvestidos na formação dos alunos.
                </p>
            </div>

            <h3 class="section-title">Como Mantemos o Espetáculo?</h3>
            <div class="support-grid">
                <div class="support-item">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Ações Comunitárias</span>
                    <small>Rifas, chás e ingressos</small>
                </div>
                <div class="support-item">
                    <i class="fas fa-cut"></i>
                    <span>Produção Artesanal</span>
                    <small>Figurinos feitos por mães voluntárias</small>
                </div>
                <div class="support-item">
                    <i class="fas fa-hands-helping"></i>
                    <span>Padrinhos</span>
                    <small>Doadores do Brasil e exterior</small>
                </div>
            </div>

            <div class="highlight-box">
                <div class="highlight-icon"><i class="fas fa-home"></i></div>
                <div class="highlight-content">
                    <h4>Nossa Grande Batalha: A Sede Própria</h4>
                    <p>Nosso maior objetivo é conquistar um estúdio com infraestrutura adequada (espelhos, barras, ventilação) para atender nossos alunos com a dignidade que merecem.</p>
                </div>
            </div>

            <h3 class="section-title">Como Você Pode Ajudar?</h3>
            <div class="help-options">
                <div class="help-card">
                    <div class="step-num">1</div>
                    <h5>Financeiramente</h5>
                    <p>Contribua para o fundo de manutenção e projeto da sede.</p>
                </div>
                <div class="help-card">
                    <div class="step-num">2</div>
                    <h5>Materiais</h5>
                    <p>Sapatilhas, collants, meias e itens de limpeza.</p>
                </div>
                <div class="help-card">
                    <div class="step-num">3</div>
                    <h5>Parcerias</h5>
                    <p>Apoio em eventos ou lanches para as crianças.</p>
                </div>
            </div>

            <div class="contact-cta">
                <h4><i class="fab fa-whatsapp"></i> Entre em Contato e Apoie</h4>
                <div class="cta-details">
                    <span><i class="fas fa-envelope"></i> grupoindependance@gmail.com</span>
                    <span><i class="fas fa-map-marker-alt"></i> Alvorada, RS</span>
                </div>
                <div class="cnpj-tag">CNPJ: 48.059.929/0001-12</div>
            </div>
        </div>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    setTimeout(() => {
        document.getElementById(tabName).classList.add("active");
    }, 10);
    evt.currentTarget.className += " active";
}
</script>

<?php include 'includes/footer.php'; ?>
