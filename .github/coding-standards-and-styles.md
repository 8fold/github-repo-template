# Coding Standards and Styles

Coding standards help multiple developers work in the same codebase and not have to answer certain questions because they are already answered by the standards. Further, whenever possible, if the language being discussed has community accepted standards or a governing body, those standards should be used. Finally, minimal exceptions can be made with just cause so long as those changes minimize cognitive load across the majority of developers working in the various codebases.

For example, the [w3c](https://www.w3.org/) is the generally accepted governing body for HTML and CSS; therefore, when it comes to answering questions or disputes related to HTML or CSS, that's where one should go. ECMAScript is the generally accepted standard for JavaScript. And, the PHP Framework Interop Group \([PHP-FIG](https://www.php-fig.org)\) is the same for PHP.

Therefore, if I, as a developer, move across projects, following these standards should minimize the learning curve required to begin contributing value.

## PHP

Standards come in the form of PHP Standards Recommendations \([PSRs](https://www.php-fig.org/psr/)\) proposals are made, debated, modified, and potentially accepted. As the PHP-FIG discusses proposals that could impact all PHP developers, the PSRs are generally not accepted lightly.

Running the following in the command line should help:

`composer run prod`

This will run the codebase through:

1. a static analyzer,
2. a code style checker (compliance with [PSR12](https://www.php-fig.org/psr/psr-12/)), and
3. the test bed.

These are all run upon submission of a pull request.
