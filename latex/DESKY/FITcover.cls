\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{FITcover}[2012/03/26 CTU FIT standard class for borders]

% created by Ondrej Guth <ondrej.guth@fit.cvut.cz>

% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % %
% options
% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % %
\newif\if@thesistypedefined
\DeclareOption{thesis=B}{\gdef\@thesistype{B}\@thesistypedefinedtrue}
\DeclareOption{thesis=M}{\gdef\@thesistype{M}\@thesistypedefinedtrue}

\newif\if@langdefined
\newif\if@langczech
\DeclareOption{czech}{
	\AtBeginDocument{\selectlanguage{czech}}
	\PassOptionsToPackage{\CurrentOption}{babel}
	\@langczechtrue
	\@langdefinedtrue
}
\DeclareOption{english}{
	\AtBeginDocument{\selectlanguage{UKenglish}}
	\PassOptionsToPackage{UKenglish}{babel}
	\@langczechfalse
	\@langdefinedtrue
}

\newif\if@inverse
\DeclareOption{inverse}{
	\@inversetrue
}

\ProcessOptions

\if@langdefined\else
	\ClassError{FITboards}{Language not specified}{Add option czech for Czech language or option english for English language.}
\fi


\LoadClass[a4paper,20pt]{extarticle}[1996/10/08]
\RequirePackage[a4paper,textwidth=171mm,textheight=230mm,rmargin=20mm,tmargin=35mm]{geometry}[2002/07/08]

\RequirePackage{babel}[2008/07/06]

\if@inverse
	\RequirePackage{color}
	\pagecolor{blue}
	\color{white}
\fi

\if@langczech
	\RequirePackage[T1]{fontenc}
\else
	\AtBeginDocument{\selectlanguage{UKenglish}}
\fi

\RequirePackage{palatino}

\RequirePackage{graphicx}


% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % 

\newif\if@titledefined
\DeclareRobustCommand{\title}[1]{
	\def\thetitle{#1}
	\@titledefinedtrue
}

\newif\if@authordefined
\DeclareRobustCommand{\author}[1]{
	\def\theauthor{#1}
	\@authordefinedtrue
}

\DeclareRobustCommand{\thesistype}{\if@thesistypedefined\if\@thesistype B\iflanguage{czech}{BAKAL{\' A}{\v R}SK{\' A} PR{\' A}CE}{BACHELOR'S THESIS}\else\if\@thesistype M\iflanguage{czech}{DIPLOMOV{\' A} PR{\' A}CE}{MASTER'S THESIS}\else\ClassError{FITboards}{Thesis type not specified}{Add option thesis=B for bachelor's thesis or option thesis=M for master's thesis.}\fi\fi\else\ClassError{FITboards}{Thesis type not specified}{Add option thesis=B for bachelor's thesis or option thesis=M for master's thesis.}\fi}




\DeclareRobustCommand{\universitytitle}{
	\iflanguage{czech}{{\v C}ESK{\' E} VYSOK{\' E} U{\v C}EN{\' I} TECHNICK{\' E} V PRAZE}{CZECH TECHNICAL UNIVERSITY IN PRAGUE}
}

\DeclareRobustCommand{\facultytitle}{
	\iflanguage{czech}{FAKULTA INFORMA{\v C}N{\' I}CH TECHNOLOGI{\' I}}{FACULTY OF INFORMATION TECHNOLOGY}
}


\AtBeginDocument{
\pagestyle{empty}

\begin{center}
	\universitytitle
	
	\vspace{10mm}
	
	\facultytitle
	
	\vspace{3cm}
	\if@inverse
		\includegraphics[height=5cm]{cvut-logo-w}
	\else
		\includegraphics[height=5cm]{cvut-logo-bw}
	\fi
	
	\vspace{3cm}
	
	\if@titledefined\thetitle\else\ClassError{FITboards}{Thesis' title unspecified}{Specify title of this thesis using the \protect\title\space command.}\fi

	\vfill
	
	\thesistype
\end{center}
	
	\vspace{10mm}
	
	\the\year{} \hfill \if@authordefined\theauthor\else\ClassError{FITboards}{Thesis' author unspecified}{Specify author of this thesis (i.e. your name) using the \protect\author\space command.}\fi
}
