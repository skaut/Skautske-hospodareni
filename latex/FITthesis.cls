\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{FITthesis}[2012/05/02 CTU FIT standard class for theses]

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
\chapterstyle{madsen}
\setbiblabel{(#1)}

\RequirePackage{babel}[2008/07/06]
\RequirePackage{hyperref}
\RequirePackage[numbers,round]{natbib}

\RequirePackage{chngcntr}
\counterwithout*{footnote}{chapter}

\if@langczech
	\RequirePackage[T1]{fontenc}
	\RequirePackage{lmodern}
\else
	\AtBeginDocument{\selectlanguage{UKenglish}}
\fi

\setlrmargins{4cm}{*}{*}\checkandfixthelayout

% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % %
% general commands
% % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % % %

\DeclareRobustCommand{\chapterstyletitle}[1]{
	\@makechapterhead{#1}
	\noindent
}

\newenvironment{introduction}{
	\setsecnumdepth{part}
	\if@langczech\chapter{{\' U}vod}\else\chapter{Introduction}\fi
}{
	\setsecnumdepth{all}
}

\newenvironment{conclusion}{
	\setsecnumdepth{part}
	\if@langczech\chapter{Z{\' a}v{\v e}r}\else\chapter{Conclusion}\fi
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

% \newif\if@declarationofauthenticitydefined
% \DeclareRobustCommand{\declarationOfAuthenticity}[1]{
% 	\def\thedeclarationofauthenticity{#1}
% 	\@declarationofauthenticitydefinedtrue
% }

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

\DeclareRobustCommand{\thesistype}{\if@thesistypedefined\if\@thesistype B\if@langczech{Bakal{\' a}{\v r}sk{\' a} pr{\' a}ce}\else{Bachelor's thesis}\fi\else\if\@thesistype M\if@langczech{Diplomov{\' a} pr{\' a}ce}\else{Master's thesis}\fi\else\ClassError{FITthesis}{Thesis type not specified}{Add option thesis=B for bachelor's thesis or option thesis=M for master's thesis.}\fi\fi\else\ClassError{FITthesis}{Thesis type not specified}{Add option thesis=B for bachelor's thesis or option thesis=M for master's thesis.}\fi}

\newif\if@declarationOptionSelected
\DeclareRobustCommand{\declarationOfAuthenticityOption}[1]{
	\ifx1#1 \DeclareRobustCommand{\thedeclarationofauthenticity}{\if@langczech{%
		Prohla\v suji, \v ze jsem p\v redlo\v zenou pr\'aci vypracoval samostatn\v e a \v ze jsem uvedl ve\v sker\'e pou\v zit\'e informa\v cn\'\i{} zdroje v~souladu s~Metodick\'ym pokynem o~etick\'e p\v r\'\i{}prav\v e vysoko\v skolsk\'ych z\'av\v ere\v cn\'ych prac\'\i{}.

		Beru na v\v edom\'\i{}, \v ze se na moji pr\'aci vztahuj\'\i{} pr\'ava a povinnosti vypl\'yvaj\'\i{}c\'\i{} ze z\'akona \v c.~121/2000~Sb., autorsk\'eho z\'akona, ve zn\v en\'\i{} pozd\v ej\v s\'\i{}ch p\v redpis{\r u}, zejm\'ena skute\v cnost, \v ze \v Cesk\'e vysok\'e u\v cen\'\i{} technick\'e v~Praze m\'a pr\'avo na uzav\v ren\'\i{} licen\v cn\'\i{} smlouvy o~u\v zit\'\i{} t\'eto pr\'ace jako \v skoln\'\i{}ho d\'\i{}la podle \S{}~60 odst.~1 autorsk\'eho z\'akona.
		}\else{%
		I hereby declare that the presented thesis is my own work and that I have cited all sources of information in accordance with the Guideline for adhering to ethical principles when elaborating an academic final thesis.
		
		I acknowledge that my thesis is subject to the rights and obligations stipulated by the Act No.~121/2000 Coll., the Copyright Act, as amended, in particular that the Czech Technical University in Prague has the right to conclude a license agreement on the utilization of this thesis as a school work under the provisions of Article~60(1) of the Act.
		}\fi
	}\@declarationOptionSelectedtrue\fi
	\ifx2#1 \DeclareRobustCommand{\thedeclarationofauthenticity}{\@declarationOptionSelectedtrue\if@langczech{%
		Prohla\v suji, \v ze jsem p\v redlo\v zenou pr\'aci vypracoval samostatn\v e a \v ze jsem uvedl ve\v sker\'e pou\v zit\'e informa\v cn\'\i{} zdroje v~souladu s~Metodick\'ym pokynem o~etick\'e p\v r\'\i{}prav\v e vysoko\v skolsk\'ych z\'av\v ere\v cn\'ych prac\'\i{}.

		Beru na v\v edom\'\i{}, \v ze se na moji pr\'aci vztahuj\'\i{} pr\'ava a povinnosti vypl\'yvaj\'\i{}c\'\i{} ze z\'akona \v c.~121/2000~Sb., autorsk\'eho z\'akona, ve zn\v en\'\i{} pozd\v ej\v s\'\i{}ch p\v redpis{\r u}. V~souladu s~ust. \S{}~46 odst.~6 tohoto z\'akona t\'\i{}mto ud\v eluji nev\'yhradn\'\i{} opr\'avn\v en\'\i{} (licenci) k~u\v zit\'\i{} t\'eto moj\'\i{} pr\'ace, a to v\v cetn\v e v\v sech po\v c\'\i{}ta\v cov\'ych program{\r u}, je\v z jsou jej\'\i{} sou\v c\'ast\'\i{} \v ci p\v r\'\i{}lohou a ve\v sker\'e jejich dokumentace (d\'ale souhrnn\v e jen \uv{D\'\i{}lo}), a to v\v sem osob\'am, kter\'e si p\v rej\'\i{} D\'\i{}lo u\v z\'\i{}t. Tyto osoby jsou opr\'avn\v eny D\'\i{}lo u\v z\'\i{}t jak\'ymkoli zp{\r u}sobem, kter\'y nesni\v zuje hodnotu D\'\i{}la, av\v sak pouze k~nev\'yd\v ele\v cn\'ym \'u\v cel{\r u}m. Toto opr\'avn\v en\'\i{} je \v casov\v e, teritori\'aln\v e i mno\v zstevn\v e neomezen\'e.
		}\else{%
		I hereby declare that the presented thesis is my own work and that I have cited all sources of information in accordance with the Guideline for adhering to ethical principles when elaborating an academic final thesis.
		
		I acknowledge that my thesis is subject to the rights and obligations stipulated by the Act No.~121/2000 Coll., the Copyright Act, as amended. In accordance with Article~46(6) of the Act, I hereby grant a nonexclusive authorization (license) to utilize this thesis, including any and all computer programs incorporated therein or attached thereto and all corresponding documentation (hereinafter collectively referred to as the ``Work''), to any and all persons that wish to utilize the Work. Such persons are entitled to use the Work for non-profit purposes only, in any way that does not detract from its value. This authorization is not limited in terms of time, location and quantity.
		}\fi
	}\fi
	\ifx3#1 \DeclareRobustCommand{\thedeclarationofauthenticity}{\if@langczech{%
		Prohla\v suji, \v ze jsem p\v redlo\v zenou pr\'aci vypracoval samostatn\v e a \v ze jsem uvedl ve\v sker\'e pou\v zit\'e informa\v cn\'\i{} zdroje v~souladu s~Metodick\'ym pokynem o~etick\'e p\v r\'\i{}prav\v e vysoko\v skolsk\'ych z\'av\v ere\v cn\'ych prac\'\i{}.

		Beru na v\v edom\'\i{}, \v ze se na moji pr\'aci vztahuj\'\i{} pr\'ava a povinnosti vypl\'yvaj\'\i{}c\'\i{} ze z\'akona \v c.~121/2000~Sb., autorsk\'eho z\'akona, ve zn\v en\'\i{} pozd\v ej\v s\'\i{}ch p\v redpis{\r u}. D\'ale prohla\v suji, \v ze jsem s~\v Cesk\'ym vysok\'ym u\v cen\'\i{}m technick\'ym v~Praze uzav\v rel licen\v cn\'\i{} smlouvu o~u\v zit\'\i{} t\'eto pr\'ace jako \v skoln\'\i{}ho d\'\i{}la podle \S{}~60 odst.~1 autorsk\'eho z\'akona. Tato skute\v cnost nem\'a vliv na ust. \S{}~47b z\'akona \v c.~111/1998~Sb., o~vysok\'ych \v skol\'ach, ve zn\v en\'\i{} pozd\v ej\v s\'\i{}ch p\v redpis{\r u}.
		}\else{%
		I hereby declare that the presented thesis is my own work and that I have cited all sources of information in accordance with the Guideline for adhering to ethical principles when elaborating an academic final thesis.
		
		I acknowledge that my thesis is subject to the rights and obligations stipulated by the Act No.~121/2000 Coll., the Copyright Act, as amended. I further declare that I have concluded a license agreement with the Czech Technical University in Prague on the utilization of this thesis as a school work under the provisions of Article~60(1) of the Act. This fact shall not affect the provisions of Article~47b of the Act No.~111/1998 Coll., the Higher Education Act, as amended.
		}\fi
	}\@declarationOptionSelectedtrue\fi
	\ifx4#1 \DeclareRobustCommand{\thedeclarationofauthenticity}{\if@langczech{%
		Prohla\v suji, \v ze jsem p\v redlo\v zenou pr\'aci vypracoval samostatn\v e a \v ze jsem uvedl ve\v sker\'e pou\v zit\'e informa\v cn\'\i{} zdroje v~souladu s~Metodick\'ym pokynem o~etick\'e p\v r\'\i{}prav\v e vysoko\v skolsk\'ych z\'av\v ere\v cn\'ych prac\'\i{}.

		Beru na v\v edom\'\i{}, \v ze se na moji pr\'aci vztahuj\'\i{} pr\'ava a povinnosti vypl\'yvaj\'\i{}c\'\i{} ze z\'akona \v c.~121/2000~Sb., autorsk\'eho z\'akona, ve zn\v en\'\i{} pozd\v ej\v s\'\i{}ch p\v redpis{\r u}. V~souladu s~ust. \S{}~46 odst.~6 tohoto z\'akona t\'\i{}mto ud\v eluji nev\'yhradn\'\i{} opr\'avn\v en\'\i{} (licenci) k~u\v zit\'\i{} t\'eto moj\'\i{} pr\'ace, a to v\v cetn\v e v\v sech po\v c\'\i{}ta\v cov\'ych program{\r u}, je\v z jsou jej\'\i{} sou\v c\'ast\'\i{} \v ci p\v r\'\i{}lohou a ve\v sker\'e jejich dokumentace (d\'ale souhrnn\v e jen \uv{D\'\i{}lo}), a to v\v sem osob\'am, kter\'e si p\v rej\'\i{} D\'\i{}lo u\v z\'\i{}t. Tyto osoby jsou opr\'avn\v eny D\'\i{}lo u\v z\'\i{}t jak\'ymkoli zp{\r u}sobem, kter\'y nesni\v zuje hodnotu D\'\i{}la a za jak\'ymkoli \'u\v celem (v\v cetn\v e u\v zit\'\i{} k~v\'yd\v ele\v cn\'ym \'u\v cel{\r u}m). Toto opr\'avn\v en\'\i{} je \v casov\v e, teritori\'aln\v e i~mno\v zstevn\v e neomezen\'e. Ka\v zd\'a osoba, kter\'a vyu\v zije v\'y\v se uvedenou licenci, se v\v sak zavazuje ud\v elit ke ka\v zd\'emu d\'\i{}lu, kter\'e vznikne (by\v t jen z\v c\'asti) na z\'aklad\v e D\'\i{}la, \'upravou D\'\i{}la, spojen\'\i{}m D\'\i{}la s~jin\'ym d\'\i{}lem, za\v razen\'\i{}m D\'\i{}la do d\'\i{}la souborn\'eho \v ci zpracov\'an\'\i{}m D\'\i{}la (v\v cetn\v e p\v rekladu), licenci alespo\v n ve v\'y\v se uveden\'em rozsahu a z\'arove\v n zp\v r\'\i{}stupnit zdrojov\'y k\'od takov\'eho d\'\i{}la alespo\v n srovnateln\'ym zp{\r u}sobem a ve srovnateln\'em rozsahu, jako je zp\v r\'\i{}stupn\v en zdrojov\'y k\'od D\'\i{}la.
		}\else{%
		I hereby declare that the presented thesis is my own work and that I have cited all sources of information in accordance with the Guideline for adhering to ethical principles when elaborating an academic final thesis.
		
		I acknowledge that my thesis is subject to the rights and obligations stipulated by the Act No.~121/2000 Coll., the Copyright Act, as amended. In accordance with Article~46(6) of the Act, I hereby grant a nonexclusive authorization (license) to utilize this thesis, including any and all computer programs incorporated therein or attached thereto and all corresponding documentation (hereinafter collectively referred to as the ``Work''), to any and all persons that wish to utilize the Work. Such persons are entitled to use the Work in any way (including for-profit purposes) that does not detract from its value. This authorization is not limited in terms of time, location and quantity. However, all persons that makes use of the above license shall be obliged to grant a license at least in the same scope as defined above with respect to each and every work that is created (wholly or in part) based on the Work, by modifying the Work, by combining the Work with another work, by including the Work in a collection of works or by adapting the Work (including translation), and at the same time make available the source code of such work at least in a way and scope that are comparable to the way and scope in which the source code of the Work is made available.
		}\fi
	}\@declarationOptionSelectedtrue\fi
	\ifx5#1 \DeclareRobustCommand{\thedeclarationofauthenticity}{\if@langczech{%
		Prohla\v suji, \v ze jsem p\v redlo\v zenou pr\'aci vypracoval samostatn\v e a \v ze jsem uvedl ve\v sker\'e pou\v zit\'e informa\v cn\'\i{} zdroje v~souladu s~Metodick\'ym pokynem o~etick\'e p\v r\'\i{}prav\v e vysoko\v skolsk\'ych z\'av\v ere\v cn\'ych prac\'\i{}.

		Beru na v\v edom\'\i{}, \v ze se na moji pr\'aci vztahuj\'\i{} pr\'ava a povinnosti vypl\'yvaj\'\i{}c\'\i{} ze z\'akona \v c.~121/2000~Sb., autorsk\'eho z\'akona, ve zn\v en\'\i{} pozd\v ej\v s\'\i{}ch p\v redpis{\r u}. V~souladu s~ust. \S{}~46 odst.~6 tohoto z\'akona t\'\i{}mto ud\v eluji nev\'yhradn\'\i{} opr\'avn\v en\'\i{} (licenci) k~u\v zit\'\i{} t\'eto moj\'\i{} pr\'ace, a to v\v cetn\v e v\v sech po\v c\'\i{}ta\v cov\'ych program{\r u}, je\v z jsou jej\'\i{} sou\v c\'ast\'\i{} \v ci p\v r\'\i{}lohou a ve\v sker\'e jejich dokumentace (d\'ale souhrnn\v e jen \uv{D\'\i{}lo}), a to v\v sem osob\'am, kter\'e si p\v rej\'\i{} D\'\i{}lo u\v z\'\i{}t. Tyto osoby jsou opr\'avn\v eny D\'\i{}lo u\v z\'\i{}t jak\'ymkoli zp{\r u}sobem, kter\'y nesni\v zuje hodnotu D\'\i{}la a za jak\'ymkoli \'u\v celem (v\v cetn\v e u\v zit\'\i{} k~v\'yd\v ele\v cn\'ym \'u\v cel{\r u}m). Toto opr\'avn\v en\'\i{} je \v casov\v e, teritori\'aln\v e i mno\v zstevn\v e neomezen\'e.
		}\else{
		I hereby declare that the presented thesis is my own work and that I have cited all sources of information in accordance with the Guideline for adhering to ethical principles when elaborating an academic final thesis.
		
		I acknowledge that my thesis is subject to the rights and obligations stipulated by the Act No.~121/2000 Coll., the Copyright Act, as amended. In accordance with Article~46(6) of the Act, I hereby grant a nonexclusive authorization (license) to utilize this thesis, including any and all computer programs incorporated therein or attached thereto and all corresponding documentation (hereinafter collectively referred to as the ``Work''), to any and all persons that wish to utilize the Work. Such persons are entitled to use the Work in any way (including for-profit purposes) that does not detract from its value. This authorization is not limited in terms of time, location and quantity.
		}\fi
	}\@declarationOptionSelectedtrue\fi
	\ifx6#1 \DeclareRobustCommand{\thedeclarationofauthenticity}{\if@langczech{%
		Prohla\v suji, \v ze jsem p\v redlo\v zenou pr\'aci vypracoval samostatn\v e a \v ze jsem uvedl ve\v sker\'e pou\v zit\'e informa\v cn\'\i{} zdroje v~souladu s~Metodick\'ym pokynem o~etick\'e p\v r\'\i{}prav\v e vysoko\v skolsk\'ych z\'av\v ere\v cn\'ych prac\'\i{}.

		Beru na v\v edom\'\i{}, \v ze se na moji pr\'aci vztahuj\'\i{} pr\'ava a povinnosti vypl\'yvaj\'\i{}c\'\i{} ze z\'akona \v c.~121/2000~Sb., autorsk\'eho z\'akona, ve zn\v en\'\i{} pozd\v ej\v s\'\i{}ch p\v redpis{\r u}. D\'ale prohla\v suji, \v ze jsem s~\v Cesk\'ym vysok\'ym u\v cen\'\i{}m technick\'ym v~Praze uzav\v rel dohodu, na z\'aklad\v e n\'\i{}\v z se \v CVUT vzdalo pr\'ava na uzav\v ren\'\i{} licen\v cn\'\i{} smlouvy o~u\v zit\'\i{} t\'eto pr\'ace jako \v skoln\'\i{}ho d\'\i{}la podle \S{}~60 odst.~1 autorsk\'eho z\'akona. Tato skute\v cnost nem\'a vliv na ust. \S{}~47b z\'akona \v c.~111/1998~Sb., o~vysok\'ych \v skol\'ach, ve zn\v en\'\i{} pozd\v ej\v s\'\i{}ch p\v redpis{\r u}.
		}\else{
		I hereby declare that the presented thesis is my own work and that I have cited all sources of information in accordance with the Guideline for adhering to ethical principles when elaborating an academic final thesis.
		
		I acknowledge that my thesis is subject to the rights and obligations stipulated by the Act No.~121/2000 Coll., the Copyright Act, as amended. I further declare that I have concluded an agreement with the Czech Technical University in Prague, on the basis of which the Czech Technical University in Prague has waived its right to conclude a license agreement on the utilization of this thesis as a school work under the provisions of Article~60(1) of the Act. This fact shall not affect the provisions of Article~47b of the Act No.~111/1998 Coll., the Higher Education Act, as amended. 
		}\fi
	}\@declarationOptionSelectedtrue\fi
	
}

\DeclareRobustCommand{\titlepage}{
	\begin{titlingpage}
		\begin{center}
			\noindent\begin{tabular*}{\textwidth}{m{.77\textwidth}m{.165\textwidth}}
				{\large\scshape \if@langczech{
						{\v C}esk{\' e} vysok{\' e} u{\v c}en{\' i} technick{\' e} v~Praze
					}\else{
						Czech Technical University in Prague
					}\fi
					
					\vspace{4mm}
					
					\if@langczech{
						Fakulta informa{\v c}n{\' i}ch technologi{\' i}
					}\else{
						Faculty of Information Technology
					}\fi
					
					\vspace{4mm}
					
					\if@departmentdefined
						\thedepartment
					\else
						\ClassError{FITthesis}{Department unspecified}{Specify the department using the \department command.}
					\fi
				} & \hfill\includegraphics[width=.16\textwidth]{cvut-logo-bw}
			\end{tabular*}
		\end{center}
		

		\vfill

		{\Large
			\noindent\thesistype
		}
		\vspace{1cm}

		\if\thetitle\empty
			\ClassError{FITthesis}{Thesis' title unspecified}{Specify title of this thesis using the \protect\title\space command.}
		\else
			\noindent\textbf{\LARGE \thetitle}
		\fi

		\vspace{4mm}

		\if@authorwithdegreesdefined
			\noindent\textbf{\Large \textit{\theauthorwithdegrees}}
		\else
			\ClassError{FITthesis}{Thesis' author with degrees unspecified}{Specify author of this thesis (i.e. your name \& degrees) using the \protect\theauthorwithdegrees\space command.}
		\fi

		\vfill

		\noindent\if@langczech{Vedouc{\' i} pr{\' a}ce:}\else{Supervisor:}\fi
			\if@supervisordefined%
				~\thesupervisor
			\else
				\ClassError{FITthesis}{Thesis' supervisor unspecified}{Specify the supervisor of this thesis using the \protect\thesupervisor\space command.}
			\fi

		\vspace{1cm}

		\noindent\today
	\end{titlingpage}
}

\DeclareRobustCommand{\acknowledgementspage}{
	\if@acknowledgementsdefined
		\cleardoublepage
		\thispagestyle{empty}
		
		~
		
		\vfill
		
		\if@langczech\chapterstyletitle{Pod{\v e}kov{\' a}n{\' i}}\else\chapterstyletitle{Acknowledgements}\fi\theacknowledgements
	\fi
}

\DeclareRobustCommand{\declarationofauthenticitypage}{
	\cleardoublepage
	
	~
	
	\vfill
		
	\if@declarationOptionSelected
		\if@langczech\chapterstyletitle{Prohl{\' a}{\v s}en{\' i}}\else\chapterstyletitle{Declaration}\fi\thedeclarationofauthenticity{}
		
		\vspace{2cm}

		\noindent
			\if@cityfordeclarationdefined
				\if@langczech{}\else{In }\fi\thecityfordeclaration{} \if@langczech{dne}\else{on}\fi~\today \hfill \dots\dots\dots\dots\dots\dots\dots
			\else
				\ClassError{FITthesis}{M{\' i}sto prohl{\' a}{\v s}en{\' i} nebylo zad{\' a}no}{Nastavte m{\' i}sto, kde bylo podeps{\' a}no prohl{\' a}{\v s}en{\' i}, v{\v c}etn{\v e} p{\v r}edlo{\v z}ky (nap{\v r}. V~Praze) pomoc{\' i} p{\v r}{\' i}kazu \placeForDeclaration.}
			\fi

	\else
		\ClassError{FITthesis}{Declaration of authenticity unspecified}{Select the declaration of authenticity using the \declarationOfAuthenticityOption command.}
	\fi
}

\DeclareRobustCommand{\imprintpage}{
	\clearpage
	\thispagestyle{empty}
	
	~
	
	\vfill
	
	\if@langczech{
		\noindent {\v C}esk{\' e} vysok{\' e} u{\v c}en{\' i} technick{\' e} v~Praze

		\noindent Fakulta informa{\v c}n{\' i}ch technologi{\' i}}\else{
		\noindent Czech Technical University in Prague
		
		\noindent Faculty of Information Technology
	}\fi
	
	\noindent \textcopyright{} \the\year{} \theauthor{}. \if@langczech{V{\v s}echna pr{\' a}va vyhrazena.}\else{All rights reserved.}\fi
	
	\if@langczech{
		\noindent \textit{Tato pr{\' a}ce vznikla jako {\v s}koln{\' i} d{\' i}lo na {\v C}esk{\' e}m vysok{\' e}m u{\v c}en{\' i} technick{\' e}m v~Praze, Fakult{\v e} informa{\v c}n{\' i}ch technologi{\' i}. Pr{\' a}ce je chr{\' a}n{\v e}na pr{\' a}vn{\' i}mi p{\v r}edpisy a mezin{\' a}rodn{\' i}mi {\' u}mluvami o~pr{\' a}vu autorsk{\' e}m a pr{\' a}vech souvisej{\' i}c{\' i}ch s~pr{\' a}vem autorsk{\' y}m. K~jej{\' i}mu u{\v z}it{\' i}, s~v{\' y}jimkou bez{\' u}platn{\' y}ch z{\' a}konn{\' y}ch licenc{\' i}, je nezbytn{\' y} souhlas autora.}}\else{
		\noindent \textit{This thesis is a school work as defined by Copyright Act of the Czech Republic. It has been submitted at Czech Technical University in Prague, Faculty of Information Technology. The thesis is protected by the Copyright Act and its usage without author's permission is prohibited (with exceptions defined by the Copyright Act).}
	}\fi
	
	\if@langczech{\subsection{Odkaz na tuto pr{\' a}ci} \theauthor{}. \textit{\thetitle{}: \thesistype{}}. Praha: {\v C}VUT v~Praze, Fakulta informa{\v c}n{\' i}ch technologi{\' i}, \the\year.}\else{\subsection{Citation of this thesis} \theauthor{}. \textit{\thetitle{}: \thesistype{}}. Czech Republic: Czech Technical University in Prague, Faculty of Information Technology, \the\year.}\fi
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

\AtBeginDocument{
	\if@langczech\frontmatter*\else\frontmatter\fi %cislovani stranek pred prvni kapitolou
	\thispagestyle{empty}
	\if@langczech{Sem vlo{\v z}te zad{\' a}n{\' i} Va{\v s}{\' i} pr{\' a}ce.}\else{Insert here your thesis' task.}\fi

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
    \lstlistoflistings

	\cleardoublepage
	\listoftables*

	\pagestyle{ruled}
	\if@langczech\mainmatter*\else\mainmatter\fi %hlavni text prace
}
