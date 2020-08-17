/** https://github.com/omichelsen/compare-versions/ The MIT License (MIT) Copyright (c) 2015-2019 Ole Michelsen*/
!function(e,r){"function"==typeof define&&define.amd?define([],r):"object"==typeof exports?module.exports=r():e.compareSemverVersions=r()}(this,function(){var e=/^v?(?:\d+)(\.(?:[x*]|\d+)(\.(?:[x*]|\d+)(\.(?:[x*]|\d+))?(?:-[\da-z\-]+(?:\.[\da-z\-]+)*)?(?:\+[\da-z\-]+(?:\.[\da-z\-]+)*)?)?)?$/i;function r(e){var r,t,n=e.replace(/^v/,"").replace(/\+.*$/,""),i=(t="-",-1===(r=n).indexOf(t)?r.length:r.indexOf(t)),o=n.substring(0,i).split(".");return o.push(n.substring(i+1)),o}function t(e){return isNaN(Number(e))?e:Number(e)}function n(r){if("string"!=typeof r)throw new TypeError("Invalid argument expected string");if(!e.test(r))throw new Error("Invalid argument not valid semver ('"+r+"' received)")}function i(e,i){[e,i].forEach(n);for(var o=r(e),f=r(i),a=0;a<Math.max(o.length-1,f.length-1);a++){var p=parseInt(o[a]||0,10),u=parseInt(f[a]||0,10);if(p>u)return 1;if(u>p)return-1}var d=o[o.length-1],s=f[f.length-1];if(d&&s){var c=d.split(".").map(t),l=s.split(".").map(t);for(a=0;a<Math.max(c.length,l.length);a++){if(void 0===c[a]||"string"==typeof l[a]&&"number"==typeof c[a])return-1;if(void 0===l[a]||"string"==typeof c[a]&&"number"==typeof l[a])return 1;if(c[a]>l[a])return 1;if(l[a]>c[a])return-1}}else if(d||s)return d?-1:1;return 0}var o=[">",">=","=","<","<="],f={">":[1],">=":[0,1],"=":[0],"<=":[-1,0],"<":[-1]};return i.compare=function(e,r,t){!function(e){if("string"!=typeof e)throw new TypeError("Invalid operator type, expected string but got "+typeof e);if(-1===o.indexOf(e))throw new TypeError("Invalid operator, expected one of "+o.join("|"))}(t);var n=i(e,r);return f[t].indexOf(n)>-1},i});

/**
 * Format version string
 * @param {string} versionString
 * @return {string}
 */
function formatVersion(versionString)
{
    if (versionString === 'XXBVERSION') {
        return '99.9.9';
    }
    return versionString.replace(/^([0-9]+)((?:\.[0-9]+)?)((?:\.[0-9]+)?)(?:[\.\s-+]?)((?:[A-Za-z]+)?)((?:\.?[0-9]+)?)/gi, function(_, major, minor, patch, preRelease, build)
    {
        const versionStrs = [major, minor || '.0', patch || '.0'];
        if(preRelease || build) versionStrs.push('-');
        if(preRelease) versionStrs.push(preRelease);
        if(build)
        {
            if(!preRelease) versionStrs.push('build');
            if(build[0] !== '.') versionStrs.push('.');
            versionStrs.push(build);
        }
        return versionStrs.join('');
    });
}

/**
 * Simplify version string
 * @param {string} versionString
 * @return {string}
 */
function simplifyVersion(versionString)
{
    return versionString.replace(/^([0-9]+)((?:\.[0-9]+)?)((?:\.[0-9]+)?)(?:[\.\s-+]?)((?:[A-Za-z]+)?)((?:\.?[0-9]+)?)/gi, function(_, major, minor, patch, preRelease, build)
    {
        const versionStrs = [major, minor || '.0'];
        if(patch && patch !== '.0' && patch !== '0') versionStrs.push(patch);
        if(preRelease || build) versionStrs.push('.');
        if(preRelease) versionStrs.push(preRelease);
        if(build)
        {
            if(!preRelease) versionStrs.push('build');
            versionStrs.push(build[0] === '.' ? build.substr(1) : build);
        }
        return versionStrs.join('');
    });
}

/**
 * Compare two versions
 * @param {string} version1
 * @param {string} version2
 * @return {number}
 */
function compareVersions(version1, version2)
{
    return window.compareSemverVersions(formatVersion(version1), formatVersion(version2))
}

window.compareVersions = compareVersions;
