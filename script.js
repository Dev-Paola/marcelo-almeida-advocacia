const menuToggle = document.querySelector(".menu-toggle");
const mobileMenu = document.querySelector("#mobile-menu");

if (!(menuToggle instanceof HTMLButtonElement) || !(mobileMenu instanceof HTMLElement)) {
  throw new Error("Os elementos do menu mobile não foram encontrados.");
}

const setMenuOpen = (isOpen) => {
  menuToggle.classList.toggle("is-open", isOpen);
  menuToggle.setAttribute("aria-expanded", String(isOpen));
  menuToggle.setAttribute("aria-label", isOpen ? "Fechar menu" : "Abrir menu");
  mobileMenu.hidden = !isOpen;
};

menuToggle.addEventListener("click", () => {
  setMenuOpen(menuToggle.getAttribute("aria-expanded") !== "true");
});

mobileMenu.addEventListener("click", (event) => {
  if (event.target instanceof Element && event.target.closest("a")) {
    setMenuOpen(false);
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && menuToggle.getAttribute("aria-expanded") === "true") {
    setMenuOpen(false);
    menuToggle.focus();
  }
});

window.matchMedia("(min-width: 769px)").addEventListener("change", (event) => {
  if (event.matches) {
    setMenuOpen(false);
  }
});

const practiceAreas = [
  {
    number: "01",
    title: "Direito Empresarial",
    description: "Assessoria jurídica estratégica para empresas, contratos, sociedades e prevenção de riscos.",
    href: "tel:+5511999999999",
    iconPaths: [
      { d: "M7 27V5.5h12V27M19 11h6v16M4 27h24", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" },
      { d: "M10 9h2m2 0h2m-4 4h2m-4 4h2m4-8h1m-1 4h1m-1 4h1m-4 10v-5h4v5", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" },
    ],
  },
  {
    number: "02",
    title: "Direito de Família",
    description: "Condução sensível e segura em divórcios, inventários, guarda e planejamento sucessório.",
    href: "tel:+5511999999999",
    iconPaths: [
      { d: "M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm8-1a2.5 2.5 0 1 0 0-5m-16 5a2.5 2.5 0 1 1 0-5", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" },
      { d: "M5.5 26v-2a6.5 6.5 0 0 1 13 0v2H5.5Zm14.5-9a5 5 0 0 1 6.5 4.8V26h-4", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" },
    ],
  },
  {
    number: "03",
    title: "Direito Civil",
    description: "Defesa dos seus interesses em relações contratuais, indenizações e conflitos patrimoniais.",
    href: "tel:+5511999999999",
    iconPaths: [
      { d: "M16 3.5 26 7v7.4c0 6.5-4.1 11.6-10 14.1-5.9-2.5-10-7.6-10-14.1V7l10-3.5Z", "stroke-width": "1.5", "stroke-linejoin": "round" },
      { d: "m11.5 15.8 3 3 6-6.2", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" },
    ],
  },
];

const practiceGrid = document.querySelector("#practice-grid");

if (!(practiceGrid instanceof HTMLElement)) {
  throw new Error("A grade de áreas de atuação não foi encontrada.");
}

const createPracticeIcon = (paths) => {
  const namespace = "http://www.w3.org/2000/svg";
  const icon = document.createElementNS(namespace, "svg");
  icon.setAttribute("class", "practice-icon");
  icon.setAttribute("viewBox", "0 0 32 32");
  icon.setAttribute("fill", "none");
  icon.setAttribute("aria-hidden", "true");
  icon.setAttribute("focusable", "false");

  paths.forEach((attributes) => {
    const path = document.createElementNS(namespace, "path");
    Object.entries(attributes).forEach(([name, value]) => path.setAttribute(name, value));
    path.setAttribute("stroke", "currentColor");
    path.setAttribute("fill", "none");
    icon.append(path);
  });

  return icon;
};

const createPracticeCard = ({ number, title, description, href, iconPaths }) => {
  const card = document.createElement("article");
  card.className = "practice-card";
  card.setAttribute("aria-labelledby", `practice-title-${number}`);

  const top = document.createElement("div");
  top.className = "practice-card-top";

  const iconFrame = document.createElement("span");
  iconFrame.className = "practice-icon-frame";
  iconFrame.append(createPracticeIcon(iconPaths));

  const index = document.createElement("span");
  index.className = "practice-number";
  index.setAttribute("aria-hidden", "true");
  index.textContent = number;
  top.append(iconFrame, index);

  const heading = document.createElement("h3");
  heading.id = `practice-title-${number}`;
  heading.textContent = title;

  const summary = document.createElement("p");
  summary.textContent = description;

  const link = document.createElement("a");
  link.className = "practice-link";
  link.href = href;
  link.setAttribute("aria-label", `Saiba mais sobre ${title} por telefone`);
  link.append(document.createTextNode("Saiba mais"));

  const arrow = document.createElementNS("http://www.w3.org/2000/svg", "svg");
  arrow.classList.add("practice-arrow");
  arrow.setAttribute("viewBox", "0 0 20 20");
  arrow.setAttribute("fill", "none");
  arrow.setAttribute("aria-hidden", "true");
  const arrowPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
  arrowPath.setAttribute("d", "M3.5 10h12m-5-5 5 5-5 5");
  arrowPath.setAttribute("stroke", "currentColor");
  arrowPath.setAttribute("stroke-width", "1.5");
  arrowPath.setAttribute("stroke-linecap", "round");
  arrowPath.setAttribute("stroke-linejoin", "round");
  arrow.append(arrowPath);
  link.append(arrow);

  card.append(top, heading, summary, link);
  return card;
};

practiceAreas.forEach((area) => practiceGrid.append(createPracticeCard(area)));

const aboutHighlights = [
  "Atendimento direto e humano",
  "Comunicação clara e transparente",
  "Estratégias sob medida",
  "Agilidade e acompanhamento",
];
const aboutHighlightsList = document.querySelector("#about-highlights");

if (!(aboutHighlightsList instanceof HTMLUListElement)) {
  throw new Error("A lista de diferenciais do advogado não foi encontrada.");
}

aboutHighlights.forEach((highlight) => {
  const item = document.createElement("li");
  item.className = "about-highlight";

  const check = document.createElement("span");
  check.className = "about-check";
  check.setAttribute("aria-hidden", "true");
  check.textContent = "✓";

  const label = document.createElement("span");
  label.textContent = highlight;

  item.append(check, label);
  aboutHighlightsList.append(item);
});

const processSteps = [
  {
    number: "01",
    title: "Conversa inicial",
    description: "Entendemos seu caso, suas prioridades e o resultado que você busca.",
  },
  {
    number: "02",
    title: "Análise estratégica",
    description: "Avaliamos documentos, riscos e definimos o caminho jurídico mais seguro.",
  },
  {
    number: "03",
    title: "Atuação próxima",
    description: "Você acompanha cada etapa com comunicação clara e atendimento direto.",
  },
];
const processStepsList = document.querySelector("#process-steps");

if (!(processStepsList instanceof HTMLOListElement)) {
  throw new Error("A lista de etapas do atendimento não foi encontrada.");
}

processSteps.forEach(({ number, title, description }) => {
  const step = document.createElement("li");
  step.className = "process-step";

  const index = document.createElement("span");
  index.className = "process-step-number";
  index.textContent = number;

  const heading = document.createElement("h3");
  heading.className = "process-step-title";
  heading.textContent = title;

  const summary = document.createElement("p");
  summary.className = "process-step-description";
  summary.textContent = description;

  step.append(index, heading, summary);
  processStepsList.append(step);
});

const testimonials = [
  {
    quote: "Encontrei não apenas um advogado extremamente competente, mas alguém que realmente entendeu o momento da minha empresa. A condução foi segura, clara e muito ágil.",
    name: "Ricardo Menezes",
    role: "Diretor executivo",
    location: "São Paulo",
  },
];
const testimonialList = document.querySelector("#testimonial-list");

if (!(testimonialList instanceof HTMLElement)) {
  throw new Error("A lista de depoimentos não foi encontrada.");
}

testimonials.forEach(({ quote, name, role, location }) => {
  const card = document.createElement("article");
  card.className = "testimonial-card";
  card.setAttribute("aria-label", `Depoimento de ${name}`);

  const quoteMark = document.createElement("span");
  quoteMark.className = "testimonial-quote-mark";
  quoteMark.setAttribute("aria-hidden", "true");
  quoteMark.textContent = "“";

  const blockquote = document.createElement("blockquote");
  blockquote.className = "testimonial-quote";
  const quoteText = document.createElement("p");
  quoteText.textContent = quote;
  blockquote.append(quoteText);

  const author = document.createElement("footer");
  author.className = "testimonial-author";

  const avatar = document.createElement("span");
  avatar.className = "testimonial-avatar";
  avatar.setAttribute("aria-hidden", "true");
  const avatarIcon = document.createElementNS("http://www.w3.org/2000/svg", "svg");
  avatarIcon.setAttribute("viewBox", "0 0 24 24");
  avatarIcon.setAttribute("fill", "none");
  const avatarHead = document.createElementNS("http://www.w3.org/2000/svg", "circle");
  avatarHead.setAttribute("cx", "12");
  avatarHead.setAttribute("cy", "8");
  avatarHead.setAttribute("r", "3.2");
  avatarHead.setAttribute("stroke", "currentColor");
  avatarHead.setAttribute("stroke-width", "1.5");
  const avatarBody = document.createElementNS("http://www.w3.org/2000/svg", "path");
  avatarBody.setAttribute("d", "M5.5 20a6.5 6.5 0 0 1 13 0");
  avatarBody.setAttribute("stroke", "currentColor");
  avatarBody.setAttribute("stroke-width", "1.5");
  avatarBody.setAttribute("stroke-linecap", "round");
  avatarIcon.append(avatarHead, avatarBody);
  avatar.append(avatarIcon);

  const authorCopy = document.createElement("div");
  authorCopy.className = "testimonial-author-copy";
  const authorName = document.createElement("strong");
  authorName.className = "testimonial-author-name";
  authorName.textContent = name;
  const authorDetails = document.createElement("span");
  authorDetails.className = "testimonial-author-details";
  authorDetails.textContent = `${role}, ${location}`;
  authorCopy.append(authorName, authorDetails);

  author.append(avatar, authorCopy);
  card.append(quoteMark, blockquote, author);
  testimonialList.append(card);
});

const footerContactDetails = [
  { label: "(11) 99999-9999", href: "tel:+5511999999999" },
  { label: "contato@marceloalmeida.adv.br", href: "mailto:contato@marceloalmeida.adv.br" },
  { label: "Av. Paulista, 1000 — São Paulo, SP", href: "" },
];
const footerNavigationLinks = [
  { label: "Início", href: "#inicio" },
  { label: "Atuação", href: "#atuacao" },
  { label: "Sobre", href: "#sobre" },
  { label: "Depoimentos", href: "#depoimentos" },
];
const oabRegistration = { organization: "OAB/SP", number: "Nº 000.000" };
const footerContact = document.querySelector("#footer-contact");
const footerNavigation = document.querySelector("#footer-navigation");
const footerCopyright = document.querySelector("#footer-copyright");
const footerOab = document.querySelector("#footer-oab");
const aboutOab = document.querySelector("#about-oab");
const aboutOabTitle = document.querySelector("#about-oab-title");
const aboutOabNumber = document.querySelector("#about-oab-number");

if (
  !(footerContact instanceof HTMLElement) ||
  !(footerNavigation instanceof HTMLElement) ||
  !(footerCopyright instanceof HTMLElement) ||
  !(footerOab instanceof HTMLElement) ||
  !(aboutOab instanceof HTMLElement) ||
  !(aboutOabTitle instanceof HTMLElement) ||
  !(aboutOabNumber instanceof HTMLElement)
) {
  throw new Error("Os elementos do rodapé ou do registro OAB não foram encontrados.");
}

footerContactDetails.forEach(({ label, href }) => {
  const entry = document.createElement(href ? "a" : "span");
  entry.className = "footer-contact-link";
  entry.textContent = label;

  if (href) {
    entry.href = href;
  }

  footerContact.append(entry);
});

footerNavigationLinks.forEach(({ label, href }) => {
  const link = document.createElement("a");
  link.className = "footer-navigation-link";
  link.href = href;
  link.textContent = label;
  footerNavigation.append(link);
});

const copyrightYear = new Date().getFullYear();
footerCopyright.textContent = `© ${copyrightYear} MARCELO ALMEIDA ADVOCACIA. TODOS OS DIREITOS RESERVADOS.`;
footerOab.textContent = `${oabRegistration.organization} ${oabRegistration.number}`;
aboutOabTitle.textContent = oabRegistration.organization;
aboutOabNumber.textContent = oabRegistration.number;
aboutOab.setAttribute("aria-label", `Inscrição ${oabRegistration.organization} ${oabRegistration.number}`);
