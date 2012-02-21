\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{FITthesis}[2011/07/15 CTU FIT standard class for theses]

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
\DeclareOption{10pt}{\PassOptionsToClass{\CurrentOption}{memoir}}
\DeclareOption{11pt}{\PassOptionsToClass{\CurrentOption}{memoir}}
\DeclareOption{12pt}{\PassOptionsToClass{\CurrentOption}{memoir}}

\ExecuteOptions{11pt,czech}
\ProcessOptions

\if@langdefined\else
	\ClassError{FITthesis}{Language not specified}{Add option czech for Czech language or option english for English language.}
\fi


\LoadClass[a4paper,twoside]{memoir}[2008/08/07]
\setsecnumdepth{all}
\chapterstyle{veelo}

\RequirePackage{babel}[2008/07/06]
\RequirePackage{hyperref}

\RequirePackage{chngcntr}
\counterwithout*{footnote}{chapter}

\if@langczech
	\RequirePackage[T1]{fontenc}
	\RequirePackage{lmodern}
\else
	\AtBeginDocument{\selectlanguage{UKenglish}}
\fi

% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % %
% general commands
% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % %

\DeclareRobustCommand{\chapterstyletitle}[1]{
	\@makechapterhead{#1}
	\noindent
}

\newenvironment{introduction}{
	\setsecnumdepth{part}
	\iflanguage{czech}{\chapter{{\' U}vod}}{\chapter{Introduction}}
}{
	\setsecnumdepth{all}
}

\newenvironment{conclusion}{
	\setsecnumdepth{part}
	\iflanguage{czech}{\chapter{Z{\' a}v{\v e}r}}{\chapter{Conclusion}}
}{
	\setsecnumdepth{all}
}

% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % %
% template
% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % %

\newif\if@supervisordefined
\DeclareRobustCommand{\supervisor}[1]{
	\def\thesupervisor{#1}
	\@supervisordefinedtrue
}

\newif\if@departmentdefined
\DeclareRobustCommand{\department}[1]{
	\def\thedepartment{#1}
	\@departmentdefinedtrue
}

\newif\if@acknowledgementsdefined
\DeclareRobustCommand{\acknowledgements}[1]{
	\def\theacknowledgements{#1}
	\@acknowledgementsdefinedtrue
}

\newif\if@abstractcsdefined
\DeclareRobustCommand{\abstractCS}[1]{
	\def\theabstractcs{#1}
	\@abstractcsdefinedtrue
}

\newif\if@abstractendefined
\DeclareRobustCommand{\abstractEN}[1]{
	\def\theabstracten{#1}
	\@abstractendefinedtrue
}

\newif\if@declarationofauthenticitydefined
\DeclareRobustCommand{\declarationOfAuthenticity}[1]{
	\def\thedeclarationofauthenticity{#1}
	\@declarationofauthenticitydefinedtrue
}

\newif\if@cityfordeclarationdefined
\DeclareRobustCommand{\placeForDeclarationOfAuthenticity}[1]{
	\def\thecityfordeclaration{#1}
	\@cityfordeclarationdefinedtrue
}

\newif\if@keywordscsdefined
\DeclareRobustCommand{\keywordsCS}[1]{
	\def\thekeywordscs{#1}
	\@keywordscsdefinedtrue
}

\newif\if@keywordsendefined
\DeclareRobustCommand{\keywordsEN}[1]{
	\def\thekeywordsen{#1}
	\@keywordsendefinedtrue
}

\newif\if@authorwithdegreesdefined
\DeclareRobustCommand{\authorWithDegrees}[1]{
	\def\theauthorwithdegrees{#1}
	\@authorwithdegreesdefinedtrue
}

\DeclareRobustCommand{\thesistype}{\if@thesistypedefined\if\@thesistype B\iflanguage{czech}{Bakal{\' a}{\v r}sk{\' a} pr{\' a}ce}{Bachelor's thesis}\else\if\@thesistype M\iflanguage{czech}{Magistersk{\' a} pr{\' a}ce}{Master's thesis}\else\ClassError{FITthesis}{Thesis type not specified}{Add option thesis=B for bachelor's thesis or option thesis=M for master's thesis.}\fi\fi\else\ClassError{FITthesis}{Thesis type not specified}{Add option thesis=B for bachelor's thesis or option thesis=M for master's thesis.}\fi}

\DeclareRobustCommand{\titlepage}{
	\begin{titlingpage}
		\begin{tabular}{m{.8\textwidth}m{.2\textwidth}}
			{\large\scshape \iflanguage{czech}{
					{\v C}esk{\' e} vysok{\' e} u{\v c}en{\' i} technick{\' e} v~Praze
				}{
					Czech Technical University in Prague
				}
				
				\vspace{4mm}
				
				\iflanguage{czech}{
					Fakulta informa{\v c}n{\' i}ch technologi{\' i}
				}{
					Faculty of Information Technology
				}
				
				\vspace{4mm}
				
				\if@departmentdefined
					\thedepartment
				\else
					\ClassError{FITthesis}{Department unspecified}{Specify the department using the \department command.}
				\fi
			} & \hfill\includegraphics[width=3cm]{cvut-logo-bw}
		\end{tabular}

		\vfill

		{\Large
			\thesistype
		}
		\vspace{1cm}

		\if\thetitle\empty
			\ClassError{FITthesis}{Thesis' title unspecified}{Specify title of this thesis using the \protect\title\space command.}
		\else
			\textbf{\LARGE \thetitle}
		\fi

		\vspace{4mm}

		\if@authorwithdegreesdefined
			\textbf{\Large \textit{\theauthorwithdegrees}}
		\else
			\ClassError{FITthesis}{Thesis' author with degrees unspecified}{Specify author of this thesis (i.e. your name \& degrees) using the \protect\theauthorwithdegrees\space command.}
		\fi

		\vfill

		\iflanguage{czech}{Vedouc{\' i} pr{\' a}ce:}{Supervisor:}
			\if@supervisordefined
				\thesupervisor
			\else
				\ClassError{FITthesis}{Thesis' supervisor unspecified}{Specify the supervisor of this thesis using the \protect\thesupervisor\space command.}
			\fi

		\vspace{1cm}

		\today
	\end{titlingpage}
}

\DeclareRobustCommand{\acknowledgementspage}{
	\if@acknowledgementsdefined
		\cleardoublepage
		\thispagestyle{empty}
		
		~
		
		\vfill
		
		\iflanguage{czech}{\chapterstyletitle{Pod{\v e}kov{\' a}n{\' i}}}{\chapterstyletitle{Acknowledgements}}\theacknowledgements
	\fi
}

\DeclareRobustCommand{\declarationofauthenticitypage}{
	\cleardoublepage
	
	~
	
	\vfill
		
	\if@declarationofauthenticitydefined
		\iflanguage{czech}{\chapterstyletitle{Prohl{\' a}{\v s}en{\' i}}}{\chapterstyletitle{Declaration}}\thedeclarationofauthenticity
	\else
		\ClassError{FITthesis}{Declaration of authenticity unspecified}{Write the declaration of authenticity using the \declarationOfAuthenticity command.}
	\fi
}

\DeclareRobustCommand{\imprintpage}{
	\clearpage
	\thispagestyle{empty}
	
	~
	
	\vfill
	
	\iflanguage{czech}{
		\noindent {\v C}esk{\' e} vysok{\' e} u{\v c}en{\' i} technick{\' e} v~Praze

		\noindent Fakulta informa{\v c}n{\' i}ch technologi{\' i}}{
		\noindent Czech Technical University in Prague
		
		\noindent Faculty of Information Technology
	}
	
	\noindent \textcopyright{} \the\year{} \theauthor{}. \iflanguage{czech}{V{\v s}echna pr{\' a}va vyhrazena.}{All rights reserved.}
	
	\iflanguage{czech}{
		\noindent \textit{Tato pr{\' a}ce vznikla jako {\v s}koln{\' i} d{\' i}lo na {\v C}esk{\' e}m vysok{\' e}m u{\v c}en{\' i} technick{\' e}m v~Praze, Fakult{\v e} informa{\v c}n{\' i}ch technologi{\' i}. Pr{\' a}ce je chr{\' a}n{\v e}na pr{\' a}vn{\' i}mi p{\v r}edpisy a mezin{\' a}rodn{\' i}mi {\' u}mluvami o~pr{\' a}vu autorsk{\' e}m a pr{\' a}vech souvisej{\' i}c{\' i}ch s~pr{\' a}vem autorsk{\' y}m. K~jej{\' i}mu u{\v z}it{\' i}, s~v{\' y}jimkou bez{\' u}platn{\' y}ch z{\' a}konn{\' y}ch licenc{\' i}, je nezbytn{\' y} souhlas autora.}}{
		\noindent \textit{This thesis is a school work as defined by Copyright Act of the Czech Republic. It has been submitted at Czech Technical University in Prague, Faculty of Information Technology. The thesis is protected by the Copyright Act and its usage without author's permission is prohibited (with exceptions defined by the Copyright Act).}
	}
	
	\iflanguage{czech}{\subsection{Odkaz na tuto pr{\' a}ci} \theauthor{}. \textit{\thetitle{}: \thesistype{}}. Praha: {\v C}VUT v~Praze, Fakulta informa{\v c}n{\' i}ch technologi{\' i}, \the\year.}{\subsection{Citation of this thesis} \theauthor{}. \textit{\thetitle{}: \thesistype{}}. Czech Republic: Czech Technical University in Prague, Faculty of Information Technology, \the\year.} 
}

\DeclareRobustCommand{\abstractpage}{
	\chapter*{Abstract}

	\if@abstractendefined
		\theabstracten
	\else
		\ClassError{FITthesis}{Abstract in English unspecified}{Specify abstract in English using the \abstractEN command.}
	\fi

	\if@keywordsendefined
		\paragraph*{Keywords} \thekeywordsen{}
	\else
		\ClassError{FITthesis}{English keywords unspecified}{Specify the keywords in English of your thesis using \keywordsEN command.}
	\fi

	\vfill

	\if@abstractcsdefined
		\chapterstyletitle{Abstrakt}\theabstractcs
	\else
		\ClassError{FITthesis}{Abstract in Czech unspecified}{Specify abstract in Czech language using the \abstractCS command.}
	\fi
	
	\if@keywordscsdefined
		\paragraph*{Kl{\' i}{\v c}ov{\' a} slova} \thekeywordscs{}
	\else
		\ClassError{FITthesis}{Czech keywords unspecified}{Specify the keywords in Czech of your thesis using \keywordsCS command.}
	\fi
	
	\vfill
}

\iflanguage{czech}{
	\declarationOfAuthenticity{Prohla{\v s}uji, {\v z}e jsem tuto pr{\' a}ci vytvo{\v r}il samostatn{\v e} a pou{\v z}il jsem pouze podklady uveden{\' e} v~p{\v r}ilo{\v z}en{\' e}m seznamu.

		\noindent
		Ve smyslu \S60 Z{\' a}kona {\v c}.~121/2000~Sb., o~pr{\' a}vu autorsk{\' e}m, o~pr{\' a}vech souvisej{\' i}c{\' i}ch s~pr{\' a}vem autorsk{\' y}m a o~zm{\v e}n{\v e} n{\v e}kter{\' y} z{\' a}kon{\r u} (autorsk{\' y} z{\' a}kon), nem{\' a}m z{\' a}va{\v z}n{\' y} d{\r u}vod proti u{\v z}it{\' i} tohoto {\v s}koln{\' i}ho d{\' i}la a k~u{\v z}it{\' i} ud{\v e}luji svolen{\' i}.

		\vspace{2cm}

		\noindent
			\if@cityfordeclarationdefined
				\thecityfordeclaration{} dne \today \hfill \dots\dots\dots\dots\dots\dots\dots
			\else
				\ClassError{FITthesis}{M{\' i}sto prohl{\' a}{\v s}en{\' i} nebylo zad{\' a}no}{Nastavte m{\' i}sto, kde bylo podeps{\' a}no prohl{\' a}{\v s}en{\' i}, v{\v c}etn{\v e} p{\v r}edlo{\v z}ky (nap{\v r}. V Praze) pomoc{\' i} p{\v r}{\' i}kazu \placeForDeclaration.}
			\fi
	}
}
{
	\declarationOfAuthenticity{I hereby declare that I have completed this thesis independently and that I have listed all the literature and publications used.

		\noindent
		I have no objection to usage of this work in compliance with the act §60 no.~121/2000 (copyright law), and with the rights connected with the copyright act included the changes in the act.

		\vspace{2cm}

		\noindent
			\if@cityfordeclarationdefined
				In \thecityfordeclaration{} \today \hfill \dots\dots\dots\dots\dots\dots\dots
			\else
				\ClassError{FITthesis}{Place of declaration unspecified}{Set the place where the declaration has been signed (e.g. Prague) using the \placeForDeclarationOfAuthenticity command.}
			\fi
	}
}


\AtBeginDocument{
	\iflanguage{czech}{\frontmatter*}{\frontmatter} %cislovani stranek pred prvni kapitolou
	\thispagestyle{empty}
	\iflanguage{czech}{Sem vlo{\v z}te zad{\' a}n{\' i} Va{\v s}{\' i} pr{\' a}ce.}{Insert here your thesis' task.}

	\pagestyle{plain}
	\cleardoublepage
	\titlepage
	
	\setcounter{page}{5}
	\acknowledgementspage

	\declarationofauthenticitypage
	\imprintpage

	\abstractpage

	\cleardoublepage
	\tableofcontents*

	\cleardoublepage
	\listoffigures*

	\cleardoublepage
	\listoftables*

	\pagestyle{ruled}
	\iflanguage{czech}{\mainmatter*}{\mainmatter} %hlavni text prace
}
