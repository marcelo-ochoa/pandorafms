<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfdecadadce22e6dde51e9535fe4ad7aa
{
    public static $files = array (
        '6124b4c8570aa390c21fafd04a26c69f' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/deep_copy.php',
        '2c102faa651ef8ea5874edb585946bce' => __DIR__ . '/..' . '/swiftmailer/swiftmailer/lib/swift_required.php',
    );

    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Tests\\' => 6,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
            'PandoraFMS\\Enterprise\\' => 22,
            'PandoraFMS\\' => 11,
        ),
        'M' => 
        array (
            'Mpdf\\' => 5,
            'Models\\' => 7,
        ),
        'E' => 
        array (
            'Enterprise\\Models\\' => 18,
            'Egulias\\EmailValidator\\' => 23,
        ),
        'D' => 
        array (
            'DeepCopy\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Tests\\' => 
        array (
            0 => __DIR__ . '/../..' . '/tests',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'PandoraFMS\\Enterprise\\' => 
        array (
            0 => __DIR__ . '/../..' . '/enterprise/include/lib',
        ),
        'PandoraFMS\\' => 
        array (
            0 => __DIR__ . '/../..' . '/include/lib',
        ),
        'Mpdf\\' => 
        array (
            0 => __DIR__ . '/..' . '/mpdf/mpdf/src',
        ),
        'Models\\' => 
        array (
            0 => __DIR__ . '/../..' . '/include/rest-api/models',
        ),
        'Enterprise\\Models\\' => 
        array (
            0 => __DIR__ . '/../..' . '/enterprise/include/rest-api/models',
        ),
        'Egulias\\EmailValidator\\' => 
        array (
            0 => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator',
        ),
        'DeepCopy\\' => 
        array (
            0 => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy',
        ),
    );

    public static $prefixesPsr0 = array (
        'D' => 
        array (
            'Doctrine\\Common\\Lexer\\' => 
            array (
                0 => __DIR__ . '/..' . '/doctrine/lexer/lib',
            ),
        ),
    );

    public static $classMap = array (
        'DeepCopy\\DeepCopy' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/DeepCopy.php',
        'DeepCopy\\Exception\\CloneException' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Exception/CloneException.php',
        'DeepCopy\\Exception\\PropertyException' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Exception/PropertyException.php',
        'DeepCopy\\Filter\\Doctrine\\DoctrineCollectionFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Filter/Doctrine/DoctrineCollectionFilter.php',
        'DeepCopy\\Filter\\Doctrine\\DoctrineEmptyCollectionFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Filter/Doctrine/DoctrineEmptyCollectionFilter.php',
        'DeepCopy\\Filter\\Doctrine\\DoctrineProxyFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Filter/Doctrine/DoctrineProxyFilter.php',
        'DeepCopy\\Filter\\Filter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Filter/Filter.php',
        'DeepCopy\\Filter\\KeepFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Filter/KeepFilter.php',
        'DeepCopy\\Filter\\ReplaceFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Filter/ReplaceFilter.php',
        'DeepCopy\\Filter\\SetNullFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Filter/SetNullFilter.php',
        'DeepCopy\\Matcher\\Doctrine\\DoctrineProxyMatcher' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Matcher/Doctrine/DoctrineProxyMatcher.php',
        'DeepCopy\\Matcher\\Matcher' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Matcher/Matcher.php',
        'DeepCopy\\Matcher\\PropertyMatcher' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Matcher/PropertyMatcher.php',
        'DeepCopy\\Matcher\\PropertyNameMatcher' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Matcher/PropertyNameMatcher.php',
        'DeepCopy\\Matcher\\PropertyTypeMatcher' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Matcher/PropertyTypeMatcher.php',
        'DeepCopy\\Reflection\\ReflectionHelper' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/Reflection/ReflectionHelper.php',
        'DeepCopy\\TypeFilter\\Date\\DateIntervalFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/TypeFilter/Date/DateIntervalFilter.php',
        'DeepCopy\\TypeFilter\\ReplaceFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/TypeFilter/ReplaceFilter.php',
        'DeepCopy\\TypeFilter\\ShallowCopyFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/TypeFilter/ShallowCopyFilter.php',
        'DeepCopy\\TypeFilter\\Spl\\SplDoublyLinkedList' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/TypeFilter/Spl/SplDoublyLinkedList.php',
        'DeepCopy\\TypeFilter\\Spl\\SplDoublyLinkedListFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/TypeFilter/Spl/SplDoublyLinkedListFilter.php',
        'DeepCopy\\TypeFilter\\TypeFilter' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/TypeFilter/TypeFilter.php',
        'DeepCopy\\TypeMatcher\\TypeMatcher' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/TypeMatcher/TypeMatcher.php',
        'Doctrine\\Common\\Lexer\\AbstractLexer' => __DIR__ . '/..' . '/doctrine/lexer/lib/Doctrine/Common/Lexer/AbstractLexer.php',
        'Egulias\\EmailValidator\\EmailLexer' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/EmailLexer.php',
        'Egulias\\EmailValidator\\EmailParser' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/EmailParser.php',
        'Egulias\\EmailValidator\\EmailValidator' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/EmailValidator.php',
        'Egulias\\EmailValidator\\Exception\\AtextAfterCFWS' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/AtextAfterCFWS.php',
        'Egulias\\EmailValidator\\Exception\\CRLFAtTheEnd' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/CRLFAtTheEnd.php',
        'Egulias\\EmailValidator\\Exception\\CRLFX2' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/CRLFX2.php',
        'Egulias\\EmailValidator\\Exception\\CRNoLF' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/CRNoLF.php',
        'Egulias\\EmailValidator\\Exception\\CharNotAllowed' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/CharNotAllowed.php',
        'Egulias\\EmailValidator\\Exception\\CommaInDomain' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/CommaInDomain.php',
        'Egulias\\EmailValidator\\Exception\\ConsecutiveAt' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/ConsecutiveAt.php',
        'Egulias\\EmailValidator\\Exception\\ConsecutiveDot' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/ConsecutiveDot.php',
        'Egulias\\EmailValidator\\Exception\\DomainHyphened' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/DomainHyphened.php',
        'Egulias\\EmailValidator\\Exception\\DotAtEnd' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/DotAtEnd.php',
        'Egulias\\EmailValidator\\Exception\\DotAtStart' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/DotAtStart.php',
        'Egulias\\EmailValidator\\Exception\\ExpectedQPair' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/ExpectingQPair.php',
        'Egulias\\EmailValidator\\Exception\\ExpectingAT' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/ExpectingAT.php',
        'Egulias\\EmailValidator\\Exception\\ExpectingATEXT' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/ExpectingATEXT.php',
        'Egulias\\EmailValidator\\Exception\\ExpectingCTEXT' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/ExpectingCTEXT.php',
        'Egulias\\EmailValidator\\Exception\\ExpectingDTEXT' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/ExpectingDTEXT.php',
        'Egulias\\EmailValidator\\Exception\\ExpectingDomainLiteralClose' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/ExpectingDomainLiteralClose.php',
        'Egulias\\EmailValidator\\Exception\\InvalidEmail' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/InvalidEmail.php',
        'Egulias\\EmailValidator\\Exception\\NoDNSRecord' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/NoDNSRecord.php',
        'Egulias\\EmailValidator\\Exception\\NoDomainPart' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/NoDomainPart.php',
        'Egulias\\EmailValidator\\Exception\\NoLocalPart' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/NoLocalPart.php',
        'Egulias\\EmailValidator\\Exception\\UnclosedComment' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/UnclosedComment.php',
        'Egulias\\EmailValidator\\Exception\\UnclosedQuotedString' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/UnclosedQuotedString.php',
        'Egulias\\EmailValidator\\Exception\\UnopenedComment' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Exception/UnopenedComment.php',
        'Egulias\\EmailValidator\\Parser\\DomainPart' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Parser/DomainPart.php',
        'Egulias\\EmailValidator\\Parser\\LocalPart' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Parser/LocalPart.php',
        'Egulias\\EmailValidator\\Parser\\Parser' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Parser/Parser.php',
        'Egulias\\EmailValidator\\Validation\\DNSCheckValidation' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/DNSCheckValidation.php',
        'Egulias\\EmailValidator\\Validation\\EmailValidation' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/EmailValidation.php',
        'Egulias\\EmailValidator\\Validation\\Error\\RFCWarnings' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/Error/RFCWarnings.php',
        'Egulias\\EmailValidator\\Validation\\Error\\SpoofEmail' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/Error/SpoofEmail.php',
        'Egulias\\EmailValidator\\Validation\\Exception\\EmptyValidationList' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/Exception/EmptyValidationList.php',
        'Egulias\\EmailValidator\\Validation\\MultipleErrors' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/MultipleErrors.php',
        'Egulias\\EmailValidator\\Validation\\MultipleValidationWithAnd' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/MultipleValidationWithAnd.php',
        'Egulias\\EmailValidator\\Validation\\NoRFCWarningsValidation' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/NoRFCWarningsValidation.php',
        'Egulias\\EmailValidator\\Validation\\RFCValidation' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/RFCValidation.php',
        'Egulias\\EmailValidator\\Validation\\SpoofCheckValidation' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Validation/SpoofCheckValidation.php',
        'Egulias\\EmailValidator\\Warning\\AddressLiteral' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/AddressLiteral.php',
        'Egulias\\EmailValidator\\Warning\\CFWSNearAt' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/CFWSNearAt.php',
        'Egulias\\EmailValidator\\Warning\\CFWSWithFWS' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/CFWSWithFWS.php',
        'Egulias\\EmailValidator\\Warning\\Comment' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/Comment.php',
        'Egulias\\EmailValidator\\Warning\\DeprecatedComment' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/DeprecatedComment.php',
        'Egulias\\EmailValidator\\Warning\\DomainLiteral' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/DomainLiteral.php',
        'Egulias\\EmailValidator\\Warning\\DomainTooLong' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/DomainTooLong.php',
        'Egulias\\EmailValidator\\Warning\\EmailTooLong' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/EmailTooLong.php',
        'Egulias\\EmailValidator\\Warning\\IPV6BadChar' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/IPV6BadChar.php',
        'Egulias\\EmailValidator\\Warning\\IPV6ColonEnd' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/IPV6ColonEnd.php',
        'Egulias\\EmailValidator\\Warning\\IPV6ColonStart' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/IPV6ColonStart.php',
        'Egulias\\EmailValidator\\Warning\\IPV6Deprecated' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/IPV6Deprecated.php',
        'Egulias\\EmailValidator\\Warning\\IPV6DoubleColon' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/IPV6DoubleColon.php',
        'Egulias\\EmailValidator\\Warning\\IPV6GroupCount' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/IPV6GroupCount.php',
        'Egulias\\EmailValidator\\Warning\\IPV6MaxGroups' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/IPV6MaxGroups.php',
        'Egulias\\EmailValidator\\Warning\\LabelTooLong' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/LabelTooLong.php',
        'Egulias\\EmailValidator\\Warning\\LocalTooLong' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/LocalTooLong.php',
        'Egulias\\EmailValidator\\Warning\\NoDNSMXRecord' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/NoDNSMXRecord.php',
        'Egulias\\EmailValidator\\Warning\\ObsoleteDTEXT' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/ObsoleteDTEXT.php',
        'Egulias\\EmailValidator\\Warning\\QuotedPart' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/QuotedPart.php',
        'Egulias\\EmailValidator\\Warning\\QuotedString' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/QuotedString.php',
        'Egulias\\EmailValidator\\Warning\\TLD' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/TLD.php',
        'Egulias\\EmailValidator\\Warning\\Warning' => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator/Warning/Warning.php',
        'FPDF_TPL' => __DIR__ . '/..' . '/setasign/fpdi/fpdf_tpl.php',
        'FPDI' => __DIR__ . '/..' . '/setasign/fpdi/fpdi.php',
        'FilterASCII85' => __DIR__ . '/..' . '/setasign/fpdi/filters/FilterASCII85.php',
        'FilterASCIIHexDecode' => __DIR__ . '/..' . '/setasign/fpdi/filters/FilterASCIIHexDecode.php',
        'FilterLZW' => __DIR__ . '/..' . '/setasign/fpdi/filters/FilterLZW.php',
        'Models\\CachedModel' => __DIR__ . '/../..' . '/include/rest-api/models/CachedModel.php',
        'Models\\Model' => __DIR__ . '/../..' . '/include/rest-api/models/Model.php',
        'Models\\VisualConsole\\Container' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Container.php',
        'Models\\VisualConsole\\Item' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Item.php',
        'Models\\VisualConsole\\Items\\BarsGraph' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/BarsGraph.php',
        'Models\\VisualConsole\\Items\\Box' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/Box.php',
        'Models\\VisualConsole\\Items\\Clock' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/Clock.php',
        'Models\\VisualConsole\\Items\\ColorCloud' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/ColorCloud.php',
        'Models\\VisualConsole\\Items\\DonutGraph' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/DonutGraph.php',
        'Models\\VisualConsole\\Items\\EventsHistory' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/EventsHistory.php',
        'Models\\VisualConsole\\Items\\Group' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/Group.php',
        'Models\\VisualConsole\\Items\\Icon' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/Icon.php',
        'Models\\VisualConsole\\Items\\Label' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/Label.php',
        'Models\\VisualConsole\\Items\\Line' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/Line.php',
        'Models\\VisualConsole\\Items\\ModuleGraph' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/ModuleGraph.php',
        'Models\\VisualConsole\\Items\\Percentile' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/Percentile.php',
        'Models\\VisualConsole\\Items\\SimpleValue' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/SimpleValue.php',
        'Models\\VisualConsole\\Items\\StaticGraph' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/Items/StaticGraph.php',
        'Models\\VisualConsole\\View' => __DIR__ . '/../..' . '/include/rest-api/models/VisualConsole/View.php',
        'Mpdf\\Barcode' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode.php',
        'Mpdf\\Barcode\\AbstractBarcode' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/AbstractBarcode.php',
        'Mpdf\\Barcode\\BarcodeException' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/BarcodeException.php',
        'Mpdf\\Barcode\\BarcodeInterface' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/BarcodeInterface.php',
        'Mpdf\\Barcode\\Codabar' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Codabar.php',
        'Mpdf\\Barcode\\Code11' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Code11.php',
        'Mpdf\\Barcode\\Code128' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Code128.php',
        'Mpdf\\Barcode\\Code39' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Code39.php',
        'Mpdf\\Barcode\\Code93' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Code93.php',
        'Mpdf\\Barcode\\EanExt' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/EanExt.php',
        'Mpdf\\Barcode\\EanUpc' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/EanUpc.php',
        'Mpdf\\Barcode\\I25' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/I25.php',
        'Mpdf\\Barcode\\Imb' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Imb.php',
        'Mpdf\\Barcode\\Msi' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Msi.php',
        'Mpdf\\Barcode\\Postnet' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Postnet.php',
        'Mpdf\\Barcode\\Rm4Scc' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/Rm4Scc.php',
        'Mpdf\\Barcode\\S25' => __DIR__ . '/..' . '/mpdf/mpdf/src/Barcode/S25.php',
        'Mpdf\\Cache' => __DIR__ . '/..' . '/mpdf/mpdf/src/Cache.php',
        'Mpdf\\Color\\ColorConverter' => __DIR__ . '/..' . '/mpdf/mpdf/src/Color/ColorConverter.php',
        'Mpdf\\Color\\ColorModeConverter' => __DIR__ . '/..' . '/mpdf/mpdf/src/Color/ColorModeConverter.php',
        'Mpdf\\Color\\ColorSpaceRestrictor' => __DIR__ . '/..' . '/mpdf/mpdf/src/Color/ColorSpaceRestrictor.php',
        'Mpdf\\Color\\NamedColors' => __DIR__ . '/..' . '/mpdf/mpdf/src/Color/NamedColors.php',
        'Mpdf\\Config\\ConfigVariables' => __DIR__ . '/..' . '/mpdf/mpdf/src/Config/ConfigVariables.php',
        'Mpdf\\Config\\FontVariables' => __DIR__ . '/..' . '/mpdf/mpdf/src/Config/FontVariables.php',
        'Mpdf\\Conversion\\DecToAlpha' => __DIR__ . '/..' . '/mpdf/mpdf/src/Conversion/DecToAlpha.php',
        'Mpdf\\Conversion\\DecToCjk' => __DIR__ . '/..' . '/mpdf/mpdf/src/Conversion/DecToCjk.php',
        'Mpdf\\Conversion\\DecToHebrew' => __DIR__ . '/..' . '/mpdf/mpdf/src/Conversion/DecToHebrew.php',
        'Mpdf\\Conversion\\DecToOther' => __DIR__ . '/..' . '/mpdf/mpdf/src/Conversion/DecToOther.php',
        'Mpdf\\Conversion\\DecToRoman' => __DIR__ . '/..' . '/mpdf/mpdf/src/Conversion/DecToRoman.php',
        'Mpdf\\CssManager' => __DIR__ . '/..' . '/mpdf/mpdf/src/CssManager.php',
        'Mpdf\\Css\\Border' => __DIR__ . '/..' . '/mpdf/mpdf/src/Css/Border.php',
        'Mpdf\\Css\\DefaultCss' => __DIR__ . '/..' . '/mpdf/mpdf/src/Css/DefaultCss.php',
        'Mpdf\\Css\\TextVars' => __DIR__ . '/..' . '/mpdf/mpdf/src/Css/TextVars.php',
        'Mpdf\\DirectWrite' => __DIR__ . '/..' . '/mpdf/mpdf/src/DirectWrite.php',
        'Mpdf\\Exception\\InvalidArgumentException' => __DIR__ . '/..' . '/mpdf/mpdf/src/Exception/InvalidArgumentException.php',
        'Mpdf\\Fonts\\FontCache' => __DIR__ . '/..' . '/mpdf/mpdf/src/Fonts/FontCache.php',
        'Mpdf\\Fonts\\FontFileFinder' => __DIR__ . '/..' . '/mpdf/mpdf/src/Fonts/FontFileFinder.php',
        'Mpdf\\Fonts\\GlyphOperator' => __DIR__ . '/..' . '/mpdf/mpdf/src/Fonts/GlyphOperator.php',
        'Mpdf\\Fonts\\MetricsGenerator' => __DIR__ . '/..' . '/mpdf/mpdf/src/Fonts/MetricsGenerator.php',
        'Mpdf\\Form' => __DIR__ . '/..' . '/mpdf/mpdf/src/Form.php',
        'Mpdf\\Gif\\ColorTable' => __DIR__ . '/..' . '/mpdf/mpdf/src/Gif/ColorTable.php',
        'Mpdf\\Gif\\FileHeader' => __DIR__ . '/..' . '/mpdf/mpdf/src/Gif/FileHeader.php',
        'Mpdf\\Gif\\Gif' => __DIR__ . '/..' . '/mpdf/mpdf/src/Gif/Gif.php',
        'Mpdf\\Gif\\Image' => __DIR__ . '/..' . '/mpdf/mpdf/src/Gif/Image.php',
        'Mpdf\\Gif\\ImageHeader' => __DIR__ . '/..' . '/mpdf/mpdf/src/Gif/ImageHeader.php',
        'Mpdf\\Gif\\Lzw' => __DIR__ . '/..' . '/mpdf/mpdf/src/Gif/Lzw.php',
        'Mpdf\\Gradient' => __DIR__ . '/..' . '/mpdf/mpdf/src/Gradient.php',
        'Mpdf\\Hyphenator' => __DIR__ . '/..' . '/mpdf/mpdf/src/Hyphenator.php',
        'Mpdf\\Image\\Bmp' => __DIR__ . '/..' . '/mpdf/mpdf/src/Image/Bmp.php',
        'Mpdf\\Image\\ImageProcessor' => __DIR__ . '/..' . '/mpdf/mpdf/src/Image/ImageProcessor.php',
        'Mpdf\\Image\\ImageTypeGuesser' => __DIR__ . '/..' . '/mpdf/mpdf/src/Image/ImageTypeGuesser.php',
        'Mpdf\\Image\\Svg' => __DIR__ . '/..' . '/mpdf/mpdf/src/Image/Svg.php',
        'Mpdf\\Image\\Wmf' => __DIR__ . '/..' . '/mpdf/mpdf/src/Image/Wmf.php',
        'Mpdf\\Language\\LanguageToFont' => __DIR__ . '/..' . '/mpdf/mpdf/src/Language/LanguageToFont.php',
        'Mpdf\\Language\\LanguageToFontInterface' => __DIR__ . '/..' . '/mpdf/mpdf/src/Language/LanguageToFontInterface.php',
        'Mpdf\\Language\\ScriptToLanguage' => __DIR__ . '/..' . '/mpdf/mpdf/src/Language/ScriptToLanguage.php',
        'Mpdf\\Language\\ScriptToLanguageInterface' => __DIR__ . '/..' . '/mpdf/mpdf/src/Language/ScriptToLanguageInterface.php',
        'Mpdf\\Log\\Context' => __DIR__ . '/..' . '/mpdf/mpdf/src/Log/Context.php',
        'Mpdf\\Mpdf' => __DIR__ . '/..' . '/mpdf/mpdf/src/Mpdf.php',
        'Mpdf\\MpdfException' => __DIR__ . '/..' . '/mpdf/mpdf/src/MpdfException.php',
        'Mpdf\\MpdfImageException' => __DIR__ . '/..' . '/mpdf/mpdf/src/MpdfImageException.php',
        'Mpdf\\Otl' => __DIR__ . '/..' . '/mpdf/mpdf/src/Otl.php',
        'Mpdf\\OtlDump' => __DIR__ . '/..' . '/mpdf/mpdf/src/OtlDump.php',
        'Mpdf\\Output\\Destination' => __DIR__ . '/..' . '/mpdf/mpdf/src/Output/Destination.php',
        'Mpdf\\PageFormat' => __DIR__ . '/..' . '/mpdf/mpdf/src/PageFormat.php',
        'Mpdf\\Pdf\\Protection' => __DIR__ . '/..' . '/mpdf/mpdf/src/Pdf/Protection.php',
        'Mpdf\\Pdf\\Protection\\UniqidGenerator' => __DIR__ . '/..' . '/mpdf/mpdf/src/Pdf/Protection/UniqidGenerator.php',
        'Mpdf\\QrCode\\QrCode' => __DIR__ . '/..' . '/mpdf/mpdf/src/QrCode/QrCode.php',
        'Mpdf\\QrCode\\QrCodeException' => __DIR__ . '/..' . '/mpdf/mpdf/src/QrCode/QrCodeException.php',
        'Mpdf\\Shaper\\Indic' => __DIR__ . '/..' . '/mpdf/mpdf/src/Shaper/Indic.php',
        'Mpdf\\Shaper\\Myanmar' => __DIR__ . '/..' . '/mpdf/mpdf/src/Shaper/Myanmar.php',
        'Mpdf\\Shaper\\Sea' => __DIR__ . '/..' . '/mpdf/mpdf/src/Shaper/Sea.php',
        'Mpdf\\SizeConverter' => __DIR__ . '/..' . '/mpdf/mpdf/src/SizeConverter.php',
        'Mpdf\\TTFontFile' => __DIR__ . '/..' . '/mpdf/mpdf/src/TTFontFile.php',
        'Mpdf\\TTFontFileAnalysis' => __DIR__ . '/..' . '/mpdf/mpdf/src/TTFontFileAnalysis.php',
        'Mpdf\\TableOfContents' => __DIR__ . '/..' . '/mpdf/mpdf/src/TableOfContents.php',
        'Mpdf\\Tag' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag.php',
        'Mpdf\\Tag\\A' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/A.php',
        'Mpdf\\Tag\\Acronym' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Acronym.php',
        'Mpdf\\Tag\\Address' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Address.php',
        'Mpdf\\Tag\\Annotation' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Annotation.php',
        'Mpdf\\Tag\\Article' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Article.php',
        'Mpdf\\Tag\\Aside' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Aside.php',
        'Mpdf\\Tag\\B' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/B.php',
        'Mpdf\\Tag\\BarCode' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/BarCode.php',
        'Mpdf\\Tag\\Bdi' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Bdi.php',
        'Mpdf\\Tag\\Bdo' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Bdo.php',
        'Mpdf\\Tag\\Big' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Big.php',
        'Mpdf\\Tag\\BlockQuote' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/BlockQuote.php',
        'Mpdf\\Tag\\BlockTag' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/BlockTag.php',
        'Mpdf\\Tag\\Bookmark' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Bookmark.php',
        'Mpdf\\Tag\\Br' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Br.php',
        'Mpdf\\Tag\\Caption' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Caption.php',
        'Mpdf\\Tag\\Center' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Center.php',
        'Mpdf\\Tag\\Cite' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Cite.php',
        'Mpdf\\Tag\\Code' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Code.php',
        'Mpdf\\Tag\\ColumnBreak' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/ColumnBreak.php',
        'Mpdf\\Tag\\Columns' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Columns.php',
        'Mpdf\\Tag\\Dd' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Dd.php',
        'Mpdf\\Tag\\Del' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Del.php',
        'Mpdf\\Tag\\Details' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Details.php',
        'Mpdf\\Tag\\Div' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Div.php',
        'Mpdf\\Tag\\Dl' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Dl.php',
        'Mpdf\\Tag\\DotTab' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/DotTab.php',
        'Mpdf\\Tag\\Dt' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Dt.php',
        'Mpdf\\Tag\\Em' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Em.php',
        'Mpdf\\Tag\\FieldSet' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/FieldSet.php',
        'Mpdf\\Tag\\FigCaption' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/FigCaption.php',
        'Mpdf\\Tag\\Figure' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Figure.php',
        'Mpdf\\Tag\\Font' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Font.php',
        'Mpdf\\Tag\\Footer' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Footer.php',
        'Mpdf\\Tag\\Form' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Form.php',
        'Mpdf\\Tag\\FormFeed' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/FormFeed.php',
        'Mpdf\\Tag\\H1' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/H1.php',
        'Mpdf\\Tag\\H2' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/H2.php',
        'Mpdf\\Tag\\H3' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/H3.php',
        'Mpdf\\Tag\\H4' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/H4.php',
        'Mpdf\\Tag\\H5' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/H5.php',
        'Mpdf\\Tag\\H6' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/H6.php',
        'Mpdf\\Tag\\HGroup' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/HGroup.php',
        'Mpdf\\Tag\\Header' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Header.php',
        'Mpdf\\Tag\\Hr' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Hr.php',
        'Mpdf\\Tag\\I' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/I.php',
        'Mpdf\\Tag\\Img' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Img.php',
        'Mpdf\\Tag\\IndexEntry' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/IndexEntry.php',
        'Mpdf\\Tag\\IndexInsert' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/IndexInsert.php',
        'Mpdf\\Tag\\InlineTag' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/InlineTag.php',
        'Mpdf\\Tag\\Input' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Input.php',
        'Mpdf\\Tag\\Ins' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Ins.php',
        'Mpdf\\Tag\\Kbd' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Kbd.php',
        'Mpdf\\Tag\\Legend' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Legend.php',
        'Mpdf\\Tag\\Li' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Li.php',
        'Mpdf\\Tag\\Main' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Main.php',
        'Mpdf\\Tag\\Mark' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Mark.php',
        'Mpdf\\Tag\\Meter' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Meter.php',
        'Mpdf\\Tag\\Nav' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Nav.php',
        'Mpdf\\Tag\\NewColumn' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/NewColumn.php',
        'Mpdf\\Tag\\NewPage' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/NewPage.php',
        'Mpdf\\Tag\\Ol' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Ol.php',
        'Mpdf\\Tag\\Option' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Option.php',
        'Mpdf\\Tag\\P' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/P.php',
        'Mpdf\\Tag\\PageBreak' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/PageBreak.php',
        'Mpdf\\Tag\\PageFooter' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/PageFooter.php',
        'Mpdf\\Tag\\PageHeader' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/PageHeader.php',
        'Mpdf\\Tag\\Pre' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Pre.php',
        'Mpdf\\Tag\\Progress' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Progress.php',
        'Mpdf\\Tag\\Q' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Q.php',
        'Mpdf\\Tag\\S' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/S.php',
        'Mpdf\\Tag\\Samp' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Samp.php',
        'Mpdf\\Tag\\Section' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Section.php',
        'Mpdf\\Tag\\Select' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Select.php',
        'Mpdf\\Tag\\SetHtmlPageFooter' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/SetHtmlPageFooter.php',
        'Mpdf\\Tag\\SetHtmlPageHeader' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/SetHtmlPageHeader.php',
        'Mpdf\\Tag\\SetPageFooter' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/SetPageFooter.php',
        'Mpdf\\Tag\\SetPageHeader' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/SetPageHeader.php',
        'Mpdf\\Tag\\Small' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Small.php',
        'Mpdf\\Tag\\Span' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Span.php',
        'Mpdf\\Tag\\Strike' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Strike.php',
        'Mpdf\\Tag\\Strong' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Strong.php',
        'Mpdf\\Tag\\Sub' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Sub.php',
        'Mpdf\\Tag\\SubstituteTag' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/SubstituteTag.php',
        'Mpdf\\Tag\\Summary' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Summary.php',
        'Mpdf\\Tag\\Sup' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Sup.php',
        'Mpdf\\Tag\\TBody' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/TBody.php',
        'Mpdf\\Tag\\TFoot' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/TFoot.php',
        'Mpdf\\Tag\\THead' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/THead.php',
        'Mpdf\\Tag\\Table' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Table.php',
        'Mpdf\\Tag\\Tag' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Tag.php',
        'Mpdf\\Tag\\Td' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Td.php',
        'Mpdf\\Tag\\TextArea' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/TextArea.php',
        'Mpdf\\Tag\\TextCircle' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/TextCircle.php',
        'Mpdf\\Tag\\Th' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Th.php',
        'Mpdf\\Tag\\Time' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Time.php',
        'Mpdf\\Tag\\Toc' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Toc.php',
        'Mpdf\\Tag\\TocEntry' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/TocEntry.php',
        'Mpdf\\Tag\\TocPageBreak' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/TocPageBreak.php',
        'Mpdf\\Tag\\Tr' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Tr.php',
        'Mpdf\\Tag\\Tt' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Tt.php',
        'Mpdf\\Tag\\Tta' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Tta.php',
        'Mpdf\\Tag\\Tts' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Tts.php',
        'Mpdf\\Tag\\Ttz' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Ttz.php',
        'Mpdf\\Tag\\U' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/U.php',
        'Mpdf\\Tag\\Ul' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/Ul.php',
        'Mpdf\\Tag\\VarTag' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/VarTag.php',
        'Mpdf\\Tag\\WatermarkImage' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/WatermarkImage.php',
        'Mpdf\\Tag\\WatermarkText' => __DIR__ . '/..' . '/mpdf/mpdf/src/Tag/WatermarkText.php',
        'Mpdf\\Ucdn' => __DIR__ . '/..' . '/mpdf/mpdf/src/Ucdn.php',
        'Mpdf\\Utils\\Arrays' => __DIR__ . '/..' . '/mpdf/mpdf/src/Utils/Arrays.php',
        'Mpdf\\Utils\\NumericString' => __DIR__ . '/..' . '/mpdf/mpdf/src/Utils/NumericString.php',
        'Mpdf\\Utils\\PdfDate' => __DIR__ . '/..' . '/mpdf/mpdf/src/Utils/PdfDate.php',
        'Mpdf\\Utils\\UtfString' => __DIR__ . '/..' . '/mpdf/mpdf/src/Utils/UtfString.php',
        'PandoraFMS\\Dashboard\\Cell' => __DIR__ . '/../..' . '/include/lib/Dashboard/Cell.php',
        'PandoraFMS\\Dashboard\\Manager' => __DIR__ . '/../..' . '/include/lib/Dashboard/Manager.php',
        'PandoraFMS\\User' => __DIR__ . '/../..' . '/include/lib/User.php',
        'PandoraFMS\\WebSockets\\WSManager' => __DIR__ . '/../..' . '/include/lib/WSManager.php',
        'PandoraFMS\\Websockets\\WebSocketServer' => __DIR__ . '/../..' . '/include/lib/WebSocketServer.php',
        'PandoraFMS\\Websockets\\WebSocketUser' => __DIR__ . '/../..' . '/include/lib/WebSocketUser.php',
        'Psr\\Log\\AbstractLogger' => __DIR__ . '/..' . '/psr/log/Psr/Log/AbstractLogger.php',
        'Psr\\Log\\InvalidArgumentException' => __DIR__ . '/..' . '/psr/log/Psr/Log/InvalidArgumentException.php',
        'Psr\\Log\\LogLevel' => __DIR__ . '/..' . '/psr/log/Psr/Log/LogLevel.php',
        'Psr\\Log\\LoggerAwareInterface' => __DIR__ . '/..' . '/psr/log/Psr/Log/LoggerAwareInterface.php',
        'Psr\\Log\\LoggerAwareTrait' => __DIR__ . '/..' . '/psr/log/Psr/Log/LoggerAwareTrait.php',
        'Psr\\Log\\LoggerInterface' => __DIR__ . '/..' . '/psr/log/Psr/Log/LoggerInterface.php',
        'Psr\\Log\\LoggerTrait' => __DIR__ . '/..' . '/psr/log/Psr/Log/LoggerTrait.php',
        'Psr\\Log\\NullLogger' => __DIR__ . '/..' . '/psr/log/Psr/Log/NullLogger.php',
        'Psr\\Log\\Test\\DummyTest' => __DIR__ . '/..' . '/psr/log/Psr/Log/Test/LoggerInterfaceTest.php',
        'Psr\\Log\\Test\\LoggerInterfaceTest' => __DIR__ . '/..' . '/psr/log/Psr/Log/Test/LoggerInterfaceTest.php',
        'fpdi_pdf_parser' => __DIR__ . '/..' . '/setasign/fpdi/fpdi_pdf_parser.php',
        'pdf_context' => __DIR__ . '/..' . '/setasign/fpdi/pdf_context.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfdecadadce22e6dde51e9535fe4ad7aa::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfdecadadce22e6dde51e9535fe4ad7aa::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitfdecadadce22e6dde51e9535fe4ad7aa::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitfdecadadce22e6dde51e9535fe4ad7aa::$classMap;

        }, null, ClassLoader::class);
    }
}